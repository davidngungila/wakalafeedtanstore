<?php

namespace App\Services;

use App\Mail\TransactionDetectedMail;
use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class TransactionNotifier
{
    /**
     * Email the cash point whenever a transaction is detected/completed,
     * gated on the notification setting and the cash point email address.
     */
    public function notifyDetected(Transaction $transaction): void
    {
        $agent = $transaction->agent ?? cash_point();

        if ($agent === null || ! filter_var($agent->email ?? '', FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $notifications = Setting::where('key', 'notifications')->value('value') ?? [];

        if ((string) ($notifications['email_transactions'] ?? '1') !== '1') {
            return;
        }

        try {
            Mail::to($agent->email)->send(new TransactionDetectedMail($transaction->loadMissing(['network', 'agent'])));
        } catch (\Throwable $e) {
            Log::warning('Transaction detected email failed', [
                'transaction' => $transaction->id,
                'to' => $agent->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
