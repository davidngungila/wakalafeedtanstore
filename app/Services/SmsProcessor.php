<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Device;
use App\Models\SmsMessage;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The automatic ingestion pipeline: a connected Android device pushes an SMS
 * batch and each message is identified, parsed, de-duplicated, validated and
 * finally turned into a transaction with matching float/cash adjustments.
 *
 * Duplicate protection has two layers:
 *   1. SMS-level: sha256(sender | body | received_at) unique per device.
 *   2. Transaction-level: an existing transaction for the same provider
 *      (network) + provider reference is never recorded twice.
 */
class SmsProcessor
{
    public function __construct(
        private readonly SmsParser $parser,
        private readonly TransactionService $transactions,
    ) {}

    /**
     * @param  array<int, array{sender: string, message: string, received_at?: string, sim_slot?: int, subscription_id?: string}>  $items
     * @return array<int, array<string, mixed>>
     */
    public function ingest(Device $device, array $items): array
    {
        $agent = $device->agent ?? cash_point();

        if ($agent === null) {
            return array_map(
                fn () => ['ok' => false, 'error' => 'Cash point not configured. An admin must set up the cash point in Settings before SMS can be recorded.'],
                $items,
            );
        }

        $results = [];
        foreach ($items as $item) {
            $results[] = DB::transaction(fn () => $this->processOne($device, $agent, $item));
        }

        if ($items !== []) {
            $device->forceFill(['last_sync_at' => now()])->save();
        }

        return $results;
    }

    /**
     * @param  array{sender: string, message: string, received_at?: string, sim_slot?: int, subscription_id?: string}  $item
     * @return array<string, mixed>
     */
    private function processOne(Device $device, Agent $agent, array $item): array
    {
        $body = trim((string) ($item['message'] ?? ''));
        $sender = trim((string) ($item['sender'] ?? ''));

        if ($body === '') {
            return ['ok' => false, 'error' => 'Empty message body.'];
        }

        $receivedAt = $this->normalizeReceivedAt($item['received_at'] ?? null);
        $hash = hash('sha256', $sender.'|'.$body.'|'.($receivedAt?->format('Y-m-d H:i:s') ?? ''));

        $duplicate = SmsMessage::query()
            ->where('device_id', $device->id)
            ->where('sms_hash', $hash)
            ->first();

        if ($duplicate) {
            return [
                'ok' => false,
                'duplicate' => true,
                'reference' => $duplicate->transaction_reference,
                'error' => 'Duplicate SMS ignored.',
            ];
        }

        $line = $device->lineFor(
            array_key_exists('subscription_id', $item) ? (string) $item['subscription_id'] : null,
            $item['sim_slot'] ?? null,
        );

        $fallbackNetwork = $line?->network ?? $device->assignedNetworks()->first();

        $provider = $this->parser->identifyProvider($sender);
        $network = $this->parser->identifyNetwork($sender, $fallbackNetwork);

        $sms = SmsMessage::create([
            'device_id' => $device->id,
            'device_line_id' => $line?->id,
            'sim_slot' => $line?->sim_slot ?? ($item['sim_slot'] ?? null),
            'agent_id' => $agent->id,
            'network_id' => $network?->id,
            'sender' => $sender,
            'provider' => $provider,
            'message_body' => $body,
            'received_at' => $receivedAt,
            'sms_hash' => $hash,
            'processing_status' => 'RECEIVED',
            'server_received_at' => now(),
        ]);

        $parsed = $this->parser->parse($body, $provider);

        $sms->update([
            'processing_status' => 'PARSED',
            'transaction_reference' => $parsed['reference'] !== '' ? $parsed['reference'] : null,
            'amount' => $parsed['amount'] > 0 ? $parsed['amount'] : null,
            'transaction_type' => $parsed['type'] !== '' ? $parsed['type'] : null,
            'customer_phone' => $parsed['customer_phone'] !== '' ? $parsed['customer_phone'] : null,
            'customer_name' => $parsed['customer_name'] !== '' ? $parsed['customer_name'] : null,
            'balance' => $parsed['balance'] > 0 ? $parsed['balance'] : null,
        ]);

        if ($network === null) {
            $sms->update([
                'processing_status' => 'FAILED',
                'processing_error' => 'Device has no network assigned.',
            ]);

            return ['ok' => false, 'sms_id' => $sms->id, 'error' => $sms->processing_error];
        }

        if ($parsed['reference'] === '' || $parsed['amount'] <= 0) {
            $sms->update([
                'processing_status' => 'NEEDS_REVIEW',
                'processing_error' => 'SMS did not match any financial template.',
            ]);

            return ['ok' => false, 'ignored_sender' => false, 'sms_id' => $sms->id, 'error' => $sms->processing_error];
        }

        $alreadyRecorded = Transaction::query()
            ->where('network_id', $network->id)
            ->where('provider_reference', $parsed['reference'])
            ->exists();

        if ($alreadyRecorded) {
            $sms->update([
                'is_duplicate' => true,
                'processing_status' => 'DUPLICATE',
                'processing_error' => 'Duplicate transaction reference already recorded.',
            ]);

            return [
                'ok' => false,
                'duplicate' => true,
                'sms_id' => $sms->id,
                'reference' => $parsed['reference'],
                'error' => $sms->processing_error,
            ];
        }

        $transaction = $this->transactions->process(
            [
                'network_id' => $network->id,
                'type' => $parsed['type'],
                'customer_name' => $parsed['customer_name'],
                'customer_phone' => $parsed['customer_phone'],
                'amount' => $parsed['amount'],
            ],
            $agent,
            null,
            'Via SMS ingest',
            $parsed['reference'],
        );

        $sms->update([
            'processing_status' => 'RECORDED',
            'processing_error' => null,
            'transaction_id' => $transaction->id,
            'received_at' => $parsed['received_at'] ?? $sms->received_at,
        ]);

        $device->forceFill(['last_sms_at' => now()])->save();

        return [
            'ok' => true,
            'sms_id' => $sms->id,
            'status' => 'processed',
            'reference' => $sms->transaction_reference,
            'type' => $sms->transaction_type,
            'amount' => (float) $sms->amount,
            'network' => $network->code,
            'sim_slot' => $sms->sim_slot,
            'line' => $line?->displayName(),
            'transaction_reference' => $transaction->reference,
        ];
    }

    private function normalizeReceivedAt(?string $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
