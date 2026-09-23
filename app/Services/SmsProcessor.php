<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\DailyOpening;
use App\Models\Device;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Services\Sms\SmsTransactionExtractor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The automatic ingestion pipeline: a connected Android device pushes an SMS
 * batch and each message is identified, parsed, de-duplicated and validated.
 * Detected financial transactions are held as APPROVAL_PENDING for a supervisor
 * to approve (SmsController@approve) before being recorded as a transaction
 * with matching float/cash adjustments.
 *
 * Duplicate protection has two layers:
 *   1. SMS-level: sha256(sender | body | received_at) unique per device.
 *   2. Transaction-level: an existing transaction - or another held message -
 *      for the same provider (network) + provider reference is never recorded
 *      twice.
 */
class SmsProcessor
{
    public function __construct(
        private readonly SmsParser $parser,
        private readonly TransactionService $transactions,
        private readonly SmsTransactionExtractor $extractor = new SmsTransactionExtractor,
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

        // Strict template-based extraction — only approved financial patterns become transactions
        $extracted = $this->extractor->extract($body, $provider, $sender, $network?->code);

        // For SMS storage, also keep legacy parser result for backward compatibility (audit)
        $legacyParsed = $this->parser->parse($body, $provider);

        $sms->update([
            'processing_status' => 'PARSED',
            'transaction_reference' => $legacyParsed['reference'] !== '' ? $legacyParsed['reference'] : ($extracted['reference'] ?? null),
            'amount' => $legacyParsed['amount'] > 0 ? $legacyParsed['amount'] : ($extracted['amount'] ?? null),
            'transaction_type' => $legacyParsed['type'] !== '' ? $legacyParsed['type'] : ($extracted['type'] ?? null),
            'customer_phone' => $legacyParsed['customer_phone'] !== '' ? $legacyParsed['customer_phone'] : ($extracted['customer_phone'] ?? null),
            'customer_name' => $legacyParsed['customer_name'] !== '' ? $legacyParsed['customer_name'] : ($extracted['customer_name'] ?? null),
            'balance' => $legacyParsed['balance'] > 0 ? $legacyParsed['balance'] : ($extracted['balance'] ?? null),
        ]);

        // Strict: if no approved template matched -> not a transaction (promo/OTP/balance-only/etc. -> NEEDS_REVIEW)
        if ($extracted === null) {
            $isPromo = $this->isPromoSms($body);
            $sms->update([
                'processing_status' => 'NEEDS_REVIEW',
                'processing_error' => $isPromo ? 'Promo/marketing SMS ignored - not a financial transaction.' : 'SMS did not match any financial template.',
            ]);

            return ['ok' => false, 'ignored_sender' => false, 'sms_id' => $sms->id, 'error' => $sms->processing_error];
        }

        // Strict requires a supported network - fallback to active network only for approved financial SMS
        if ($network === null) {
            $network = Network::active()->first() ?? Network::first();

            if ($network === null) {
                $sms->update([
                    'processing_status' => 'FAILED',
                    'processing_error' => 'Device has no network assigned and no fallback network exists.',
                ]);

                return ['ok' => false, 'sms_id' => $sms->id, 'error' => $sms->processing_error];
            }

            $sms->update(['network_id' => $network->id]);
        }

        // Use strict extracted fields (guaranteed to have reference, amount, type, date/time etc.)
        $parsed = $extracted;
        // Ensure phone placeholder for strict (should always have phone, but fallback to UNKNOWN if missing)
        if (($parsed['customer_phone'] ?? '') === '') {
            $parsed['customer_phone'] = 'UNKNOWN';
        }
        // Persist strict extracted values to SMS for audit (overwrite legacy if needed)
        $sms->update([
            'transaction_reference' => $parsed['reference'],
            'amount' => $parsed['amount'],
            'transaction_type' => $parsed['type'],
            'customer_phone' => $parsed['customer_phone'] ?? null,
            'customer_name' => $parsed['customer_name'] ?? null,
            'balance' => $parsed['balance'] ?? null,
            'processing_error' => null,
        ]);

        $alreadyRecorded = Transaction::query()
            ->where('network_id', $network->id)
            ->where('provider_reference', $parsed['reference'])
            ->exists();

        $alreadyHeld = SmsMessage::query()
            ->whereKeyNot($sms->id)
            ->where('network_id', $network->id)
            ->where('transaction_reference', $parsed['reference'])
            ->where('processing_status', 'APPROVAL_PENDING')
            ->exists();

        if ($alreadyRecorded || $alreadyHeld) {
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

        $device->forceFill(['last_sms_at' => now()])->save();

        try {
            $sms->update([
                'processing_error' => null,
                'received_at' => $parsed['received_at'] ?? $sms->received_at,
            ]);

            $transaction = $this->recordApproved($sms, null);

            return [
                'ok' => true,
                'sms_id' => $sms->id,
                'status' => 'recorded',
                'reference' => $sms->transaction_reference,
                'type' => $sms->transaction_type,
                'amount' => (float) $sms->amount,
                'network' => $network->code,
                'sim_slot' => $sms->sim_slot,
                'line' => $line?->displayName(),
                'transaction_reference' => $transaction->reference,
                'auto_recorded' => true,
            ];
        } catch (\Throwable $e) {
            \Log::warning('SMS auto-record failed, marked for review', [
                'error' => $e->getMessage(),
                'sms_id' => $sms->id,
                'reference' => $parsed['reference'],
            ]);

            $sms->update([
                'processing_status' => 'NEEDS_REVIEW',
                'processing_error' => 'Auto-record failed: '.$e->getMessage(),
            ]);

            return [
                'ok' => false,
                'sms_id' => $sms->id,
                'status' => 'needs_review',
                'reference' => $sms->transaction_reference,
                'type' => $sms->transaction_type,
                'amount' => (float) $sms->amount,
                'network' => $network->code,
                'sim_slot' => $sms->sim_slot,
                'line' => $line?->displayName(),
                'transaction_reference' => null,
                'auto_record_error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Approve a detected (`APPROVAL_PENDING`) SMS and record it as a full
     * transaction with matching float/cash adjustments, the same way ingest
     * used to. Returns the created transaction.
     */
    public function recordApproved(SmsMessage $sms, ?int $performedBy = null): Transaction
    {
        $agent = $sms->agent ?? cash_point();

        if ($agent === null) {
            throw new \RuntimeException('Cash point not configured. An admin must set up the cash point in Settings before SMS can be recorded.');
        }

        $network = $sms->network;

        if ($network === null) {
            throw new \RuntimeException('SMS has no network assigned.');
        }

        // Re-run strict extraction to recover commission and any fields not stored on the SMS.
        $extracted = $this->extractor->extract($sms->message_body, $sms->provider, $sms->sender, $network->code);

        $parsed = $extracted ?? [
            'type' => $sms->transaction_type,
            'amount' => $sms->amount,
            'reference' => $sms->transaction_reference,
            'customer_name' => $sms->customer_name,
            'customer_phone' => $sms->customer_phone,
            'received_at' => $sms->received_at,
        ];

        $commissionOverride = isset($parsed['commission']) && (float) $parsed['commission'] > 0 ? (float) $parsed['commission'] : null;

        $transaction = $this->transactions->process(
            [
                'network_id' => $network->id,
                'type' => $parsed['type'],
                'customer_name' => $parsed['customer_name'],
                'customer_phone' => $parsed['customer_phone'],
                'amount' => $parsed['amount'],
            ],
            $agent,
            $performedBy,
            'Via SMS approval (SMS #'.$sms->id.')',
            $parsed['reference'],
            $commissionOverride,
        );

        $isFloatSms = in_array($parsed['type'], ['bank_to_wallet', 'float_topup', 'cash_in'], true)
            || str_contains(strtolower($sms->message_body), 'union financial')
            || str_contains(strtolower($sms->message_body), 'kiasi:tsh')
            || str_contains(strtolower($sms->message_body), 'kiasi: tsh');

        $this->recordFloatDual($agent, $network, $parsed, $performedBy, 'Via SMS approval - ', $isFloatSms);

        $sms->update([
            'processing_status' => 'RECORDED',
            'processing_error' => null,
            'transaction_id' => $transaction->id,
            'transaction_reference' => $parsed['reference'],
            'amount' => $parsed['amount'],
            'transaction_type' => $parsed['type'],
            'received_at' => $parsed['received_at'] ?? $sms->received_at,
        ]);

        return $transaction;
    }

    /**
     * For float-related SMS (bank_to_wallet, float_topup, cash_in, or any with
     * UNION FINANCIAL / Kiasi:Tsh), also record a matching FloatTransaction
     * linked to the daily opening.
     */
    private function recordFloatDual(Agent $agent, Network $network, array $parsed, ?int $performedBy, string $notesPrefix, bool $isFloatSms): void
    {
        if (! $isFloatSms) {
            return;
        }

        try {
            $floatType = $parsed['type'] === 'bank_to_wallet' ? 'float_topup' : 'cash_in';
            $dailyOpening = DailyOpening::forAgentAndDate($agent->id, today())->open()->first();

            FloatTransaction::create([
                'reference' => 'FLT-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'agent_id' => $agent->id,
                'network_id' => $network->id,
                'type' => $floatType,
                'amount' => $parsed['amount'],
                'fee' => 0,
                'status' => 'completed',
                'performed_by' => $performedBy,
                'notes' => $notesPrefix.'float deposit from '.($parsed['customer_name'] ?? 'bank').' ('.$parsed['reference'].')',
                'daily_opening_id' => $dailyOpening?->id,
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Float dual recording failed', ['error' => $e->getMessage()]);
        }
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

    private function fallbackAmount(string $body): float
    {
        // Find earliest TZS amount in the body, handling both "1,000 TZS" and "TZS 1,000"
        // Prefer the first occurrence whichever style, to avoid picking balance (101k) over transaction (1k)
        $candidates = [];

        if (preg_match('/([\d,]+(?:\.\d+)?)\s*TZS/i', $body, $m, PREG_OFFSET_CAPTURE) === 1) {
            $candidates[] = ['value' => (float) str_replace(',', '', $m[1][0]), 'pos' => $m[1][1]];
        }

        if (preg_match('/TZS\s+([\d,]+(?:\.\d+)?)/i', $body, $m, PREG_OFFSET_CAPTURE) === 1) {
            $candidates[] = ['value' => (float) str_replace(',', '', $m[1][0]), 'pos' => $m[0][1]];
        }

        if ($candidates !== []) {
            usort($candidates, fn ($a, $b) => $a['pos'] <=> $b['pos']);

            return $candidates[0]['value'];
        }

        if (preg_match('/Tsh\.?\s*([\d,]+(?:\.\d+)?)/i', $body, $m) === 1) {
            return (float) str_replace(',', '', $m[1]);
        }

        return 0.0;
    }

    private function fallbackReference(string $body, string $hash): string
    {
        // Prefer explicit Tnx/Txn reference like "Tnx 6263421245180145"
        if (preg_match('/\bTnx\s*[:\-]?\s*([A-Z0-9]{8,20})/i', $body, $m) === 1) {
            return strtoupper($m[1]);
        }

        if (preg_match('/\bTxn\s*[:\-]?\s*([A-Z0-9]{8,20})/i', $body, $m) === 1) {
            return strtoupper($m[1]);
        }

        if (preg_match('/Transaction\s+ID\s*[:\-]?\s*([A-Z0-9]{5,20})/i', $body, $m) === 1) {
            return strtoupper($m[1]);
        }

        return 'GEN-'.strtoupper(substr($hash, 0, 8));
    }

    private function fallbackCommission(string $body): float
    {
        if (preg_match('/Preview\s+Commission\s*:\s*([\d,]+(?:\.\d+)?)\s*TZS/i', $body, $m) === 1) {
            return (float) str_replace(',', '', $m[1]);
        }

        if (preg_match('/Commission\s*:\s*([\d,]+(?:\.\d+)?)\s*TZS/i', $body, $m) === 1) {
            return (float) str_replace(',', '', $m[1]);
        }

        return 0.0;
    }

    private function fallbackType(string $body): string
    {
        $lower = strtolower($body);

        if (str_contains($lower, 'received') || str_contains($lower, 'deposited') || str_contains($lower, 'credited') || str_contains($lower, 'umepokea') || str_contains($lower, 'umeweka')) {
            return 'deposit';
        }

        if (str_contains($lower, 'withdrawn') || str_contains($lower, 'cash out') || str_contains($lower, 'cashout') || str_contains($lower, 'umetoa')) {
            return 'withdrawal';
        }

        if (str_contains($lower, 'sent') || str_contains($lower, 'transferred') || str_contains($lower, 'umituma') || str_contains($lower, 'umetuma')) {
            return 'send_money';
        }

        if (str_contains($lower, 'paid') || str_contains($lower, 'payment') || str_contains($lower, 'bill') || str_contains($lower, 'lipa') || str_contains($lower, 'umelipa')) {
            return 'bill_payment';
        }

        if (str_contains($lower, 'airtime')) {
            return 'airtime';
        }

        if (str_contains($lower, 'bundle') || str_contains($lower, 'data ')) {
            return 'data';
        }

        return 'deposit';
    }

    private function fallbackPhone(string $body): ?string
    {
        if (preg_match('/\((0\d{9,10})\)/', $body, $m) === 1) {
            return $m[1];
        }

        if (preg_match('/(\+255\d{9}|0\d{9,10})\b/', $body, $m) === 1) {
            return $m[0];
        }

        return null;
    }

    private function isPromoSms(string $body): bool
    {
        $lower = strtolower($body);

        // Explicit promo markers - if any of these appear, it's not a financial transaction
        $promoMarkers = [
            'pata dakika',
            'mitandao yote',
            'bofya',
            'tinyurl',
            'piga *',
            'chagua',
            ' uni ofa',
            'kwa siku 7',
            'kwa siku',
            'siku 7',
            'pata ofa',
        ];

        foreach ($promoMarkers as $marker) {
            if (str_contains($lower, $marker)) {
                return true;
            }
        }

        // If it contains TSH without proper financial context (IMEFANIKIWA/Tnx/Salio), treat as promo
        // Financial SMS always contains IMEFANIKIWA or Tnx + Umeweka/Umetoa + Salio jipya
        $hasFinancialMarkers = str_contains($lower, 'imefanikiwa') || str_contains($lower, 'tnx') || str_contains($lower, 'salio jipya') || str_contains($lower, 'umeweka') || str_contains($lower, 'umetoa');
        $hasPromoStructure = str_contains($lower, 'pata') && str_contains($lower, 'kwa tsh');

        if ($hasPromoStructure && ! $hasFinancialMarkers) {
            return true;
        }

        return false;
    }
}
