<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Device;
use App\Models\SmsMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The automatic ingestion pipeline: a connected Android device pushes an SMS
 * batch and each message is identified, parsed, de-duplicated, validated and
 * finally turned into a transaction with matching float/cash adjustments.
 */
class SmsProcessor
{
    public function __construct(
        private readonly SmsParser $parser,
        private readonly TransactionService $transactions,
    ) {}

    /**
     * @param  array<int, array{sender: string, message: string, received_at?: string}>  $items
     * @return array<int, array<string, mixed>>
     */
    public function ingest(Device $device, array $items): array
    {
        $agent = $device->agent ?? cash_point();

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
     * @param  array{sender: string, message: string, received_at?: string}  $item
     * @return array<string, mixed>
     */
    private function processOne(Device $device, Agent $agent, array $item): array
    {
        $body = trim((string) ($item['message'] ?? ''));
        $sender = trim((string) ($item['sender'] ?? ''));

        if ($body === '') {
            return ['ok' => false, 'error' => 'Empty message body.'];
        }

        $hash = hash('sha256', $body);

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

        $network = $this->parser->identifyNetwork($sender, null);

        $sms = SmsMessage::create([
            'device_id' => $device->id,
            'agent_id' => $agent->id,
            'network_id' => $network?->id,
            'sender' => $sender,
            'message_body' => $body,
            'received_at' => $this->normalizeReceivedAt($item['received_at'] ?? null),
            'sms_hash' => $hash,
            'processing_status' => $network ? 'identified' : 'received',
            'server_received_at' => now(),
        ]);

        $parsed = $this->parser->parse($body);

        $sms->update([
            'processing_status' => 'parsed',
            'transaction_reference' => $parsed['reference'] !== '' ? $parsed['reference'] : null,
            'amount' => $parsed['amount'] > 0 ? $parsed['amount'] : null,
            'transaction_type' => $parsed['type'] !== '' ? $parsed['type'] : null,
            'customer_phone' => $parsed['customer_phone'] !== '' ? $parsed['customer_phone'] : null,
            'customer_name' => $parsed['customer_name'] !== '' ? $parsed['customer_name'] : null,
            'balance' => $parsed['balance'] > 0 ? $parsed['balance'] : null,
        ]);

        if ($network === null) {
            $sms->update([
                'processing_status' => 'failed',
                'processing_error' => 'Unsupported sender network.',
            ]);

            return [
                'ok' => false,
                'ignored_sender' => true,
                'sms_id' => $sms->id,
                'sender' => $sender,
                'error' => $sms->processing_error,
            ];
        }

        if ($parsed['reference'] === '' || $parsed['amount'] <= 0) {
            $sms->update([
                'processing_status' => 'failed',
                'processing_error' => 'SMS did not match any financial template.',
            ]);

            return ['ok' => false, 'ignored_sender' => false, 'sms_id' => $sms->id, 'error' => $sms->processing_error];
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
            'processing_status' => 'processed',
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
