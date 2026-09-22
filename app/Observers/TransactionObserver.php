<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\TransactionJournalService;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    public function created(Transaction $transaction): void
    {
        if ($transaction->status !== 'completed') {
            return;
        }

        try {
            app(TransactionJournalService::class)->postForTransaction($transaction->fresh(['network']));
        } catch (\Throwable $e) {
            Log::warning('TransactionObserver post failed', [
                'transaction' => $transaction->id,
                'reference' => $transaction->reference,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updated(Transaction $transaction): void
    {
        if (! $transaction->wasChanged('status')) {
            return;
        }

        if ($transaction->status === 'reversed' && $transaction->getOriginal('status') === 'completed') {
            try {
                app(TransactionJournalService::class)->reverseForTransaction($transaction->fresh(), $transaction->reversed_by);
            } catch (\Throwable $e) {
                Log::warning('TransactionObserver reverse failed', [
                    'transaction' => $transaction->id,
                    'reference' => $transaction->reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // If a completed transaction was created via update (e.g., status changed to completed), ensure posting
        if ($transaction->status === 'completed' && $transaction->getOriginal('status') !== 'completed') {
            try {
                app(TransactionJournalService::class)->postForTransaction($transaction->fresh(['network']));
            } catch (\Throwable $e) {
                Log::warning('TransactionObserver post on update failed', [
                    'transaction' => $transaction->id,
                    'reference' => $transaction->reference,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
