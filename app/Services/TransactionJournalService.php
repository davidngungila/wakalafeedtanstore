<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Network;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransactionJournalService
{
    private const CODE_CASH = '1000';

    private const CODE_FLOAT_PARENT = '1200';

    private const CODE_LIABILITY = '2100';

    private const CODE_COMMISSION = '4000';

    private const CODE_FEE = '4100';

    private const FLOAT_MAP = [
        'VODACOM' => '1210',
        'AIRTEL' => '1220',
        'HALOPESA' => '1230',
        'TIGOPESA' => '1240',
    ];

    private const DEPOSIT_LIKE = ['deposit', 'float_deposit', 'float_topup', 'bank_to_wallet', 'airtime'];

    private const WITHDRAWAL_LIKE = ['withdrawal', 'wallet_to_bank'];

    private const FLOAT_OUT_ONLY = ['send_money', 'bill_payment', 'data'];

    /**
     * Auto-post a balanced journal entry for a completed transaction.
     *
     * Net-float method (approved):
     *  - deposit-like (cash +A, float −A): Dr Cash A | Cr Float(A−C−F) | Cr Commission C | Cr Fee F
     *  - withdrawal-like (cash −A, float +A): Dr Float(A+C+F) | Cr Cash A | Cr Commission C | Cr Fee F
     *  - float-out only (cash 0, float −A): Dr Customer Float Liability A | Cr Float(A−C−F) | Cr Commission C | Cr Fee F
     */
    public function postForTransaction(Transaction $transaction): ?JournalEntry
    {
        if ($transaction->status !== 'completed') {
            return null;
        }

        if (JournalEntry::where('reference', $transaction->reference)->exists()) {
            return JournalEntry::where('reference', $transaction->reference)->first();
        }

        $amount = (float) $transaction->amount;
        $commission = (float) ($transaction->commission ?? 0);
        $fee = (float) ($transaction->fee ?? 0);
        $net = $commission + $fee;

        if ($amount <= 0 && $net <= 0) {
            return null;
        }

        try {
            $cashAccount = $this->ensureAccount(self::CODE_CASH, 'Cash on Hand', Account::TYPE_ASSET);
            $commissionAccount = $this->ensureAccount(self::CODE_COMMISSION, 'Commission Income', Account::TYPE_INCOME);
            $feeAccount = $this->ensureAccount(self::CODE_FEE, 'Fee Income', Account::TYPE_INCOME);
            $liabilityAccount = $this->ensureAccount(self::CODE_LIABILITY, 'Customer Float Liability', Account::TYPE_LIABILITY);
            $floatAccount = $this->resolveFloatAccount($transaction->network);

            $type = $transaction->type;
            $lines = [];

            if (in_array($type, self::DEPOSIT_LIKE, true)) {
                // Dr Cash A | Cr Float(A−net) | Cr Commission C | Cr Fee F
                $floatNet = $amount - $net;
                $lines[] = $this->line($cashAccount->id, $amount, 0, 'Cash received — '.txn_type_label($type));

                if ($floatNet >= 0) {
                    if ($floatNet > 0) {
                        $lines[] = $this->line($floatAccount->id, 0, $floatNet, 'Float out — '.txn_type_label($type));
                    }
                } else {
                    // net > amount: float actually increases (rare edge)
                    $lines[] = $this->line($floatAccount->id, abs($floatNet), 0, 'Float in (net) — '.txn_type_label($type));
                }

                if ($commission > 0) {
                    $lines[] = $this->line($commissionAccount->id, 0, $commission, 'Commission — '.$transaction->reference);
                }

                if ($fee > 0) {
                    $lines[] = $this->line($feeAccount->id, 0, $fee, 'Fee — '.$transaction->reference);
                }
            } elseif (in_array($type, self::WITHDRAWAL_LIKE, true)) {
                // Dr Float(A+net) | Cr Cash A | Cr Commission C | Cr Fee F
                // wallet_to_bank is treated as withdrawal-like for the ledger
                $floatGross = $amount + $net;
                $lines[] = $this->line($floatAccount->id, $floatGross, 0, 'Float in — '.txn_type_label($type));
                $lines[] = $this->line($cashAccount->id, 0, $amount, 'Cash paid out — '.txn_type_label($type));

                if ($commission > 0) {
                    $lines[] = $this->line($commissionAccount->id, 0, $commission, 'Commission — '.$transaction->reference);
                }

                if ($fee > 0) {
                    $lines[] = $this->line($feeAccount->id, 0, $fee, 'Fee — '.$transaction->reference);
                }
            } elseif (in_array($type, self::FLOAT_OUT_ONLY, true)) {
                // Dr Liability A | Cr Float(A−net) | Cr Commission C | Cr Fee F
                $floatNet = $amount - $net;
                $lines[] = $this->line($liabilityAccount->id, $amount, 0, 'Customer liability — '.txn_type_label($type));

                if ($floatNet >= 0) {
                    if ($floatNet > 0) {
                        $lines[] = $this->line($floatAccount->id, 0, $floatNet, 'Float out — '.txn_type_label($type));
                    }
                } else {
                    $lines[] = $this->line($floatAccount->id, abs($floatNet), 0, 'Float in (net) — '.txn_type_label($type));
                }

                if ($commission > 0) {
                    $lines[] = $this->line($commissionAccount->id, 0, $commission, 'Commission — '.$transaction->reference);
                }

                if ($fee > 0) {
                    $lines[] = $this->line($feeAccount->id, 0, $fee, 'Fee — '.$transaction->reference);
                }
            } else {
                // Fallback: treat as deposit-like
                $floatNet = $amount - $net;
                $lines[] = $this->line($cashAccount->id, $amount, 0, 'Cash — '.txn_type_label($type));

                if ($floatNet >= 0) {
                    if ($floatNet > 0) {
                        $lines[] = $this->line($floatAccount->id, 0, $floatNet, 'Float out — '.txn_type_label($type));
                    }
                } else {
                    $lines[] = $this->line($floatAccount->id, abs($floatNet), 0, 'Float in (net) — '.txn_type_label($type));
                }

                if ($commission > 0) {
                    $lines[] = $this->line($commissionAccount->id, 0, $commission, 'Commission — '.$transaction->reference);
                }

                if ($fee > 0) {
                    $lines[] = $this->line($feeAccount->id, 0, $fee, 'Fee — '.$transaction->reference);
                }
            }

            // Filter zero-amount lines and ensure at least 2 lines remain
            $lines = array_values(array_filter($lines, fn (array $l) => (float) $l['debit'] > 0 || (float) $l['credit'] > 0));

            if (count($lines) < 2) {
                return null;
            }

            $debits = array_sum(array_column($lines, 'debit'));
            $credits = array_sum(array_column($lines, 'credit'));

            if (abs($debits - $credits) > 0.01) {
                Log::warning('Transaction journal imbalance', [
                    'reference' => $transaction->reference,
                    'type' => $type,
                    'debits' => $debits,
                    'credits' => $credits,
                    'amount' => $amount,
                    'commission' => $commission,
                    'fee' => $fee,
                ]);

                return null;
            }

            $entryDate = $transaction->created_at ? $transaction->created_at->toDateString() : today()->toDateString();

            return DB::transaction(function () use ($transaction, $entryDate, $lines) {
                $entry = JournalEntry::create([
                    'entry_date' => $entryDate,
                    'reference' => $transaction->reference,
                    'description' => 'Auto-posting for '.$transaction->reference.' — '.txn_type_label($transaction->type).' '.money($transaction->amount),
                    'status' => JournalEntry::STATUS_POSTED,
                    'created_by' => $transaction->performed_by,
                    'posted_by' => $transaction->performed_by,
                    'posted_at' => now(),
                ]);

                foreach ($lines as $line) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $entry->id,
                        'account_id' => $line['account_id'],
                        'description' => $line['description'],
                        'debit' => $line['debit'],
                        'credit' => $line['credit'],
                    ]);
                }

                return $entry;
            });
        } catch (\Throwable $e) {
            Log::warning('Auto journal posting failed', [
                'reference' => $transaction->reference,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Reverse the auto-posted journal entry for a transaction.
     */
    public function reverseForTransaction(Transaction $transaction, ?int $reversedBy = null): ?JournalEntry
    {
        $original = JournalEntry::where('reference', $transaction->reference)->first();

        if ($original === null) {
            return null;
        }

        if ($original->status !== JournalEntry::STATUS_POSTED) {
            return null;
        }

        $reversedBy ??= $transaction->reversed_by ?? auth()->id();

        return DB::transaction(function () use ($original, $reversedBy) {
            $newReference = 'RVS-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

            while (JournalEntry::where('reference', $newReference)->exists()) {
                $newReference = 'RVS-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            }

            $entry = JournalEntry::create([
                'entry_date' => today()->toDateString(),
                'reference' => $newReference,
                'description' => 'Reversal of '.$original->reference.' — '.$original->description,
                'status' => JournalEntry::STATUS_POSTED,
                'created_by' => $reversedBy,
                'posted_by' => $reversedBy,
                'posted_at' => now(),
            ]);

            foreach ($original->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line->account_id,
                    'description' => 'Reversal of '.($line->description ?: $original->description),
                    'debit' => (float) $line->credit,
                    'credit' => (float) $line->debit,
                ]);
            }

            $original->update(['status' => JournalEntry::STATUS_REVERSED]);

            return $entry;
        });
    }

    private function resolveFloatAccount(?Network $network): Account
    {
        $code = self::CODE_FLOAT_PARENT;

        if ($network !== null) {
            $code = self::FLOAT_MAP[$network->code] ?? self::CODE_FLOAT_PARENT;
        }

        $names = [
            '1200' => 'Mobile Money Float',
            '1210' => 'Float — Vodacom',
            '1220' => 'Float — Airtel',
            '1230' => 'Float — Mixx (HaloPesa)',
            '1240' => 'Float — Tigo',
        ];

        if ($code !== self::CODE_FLOAT_PARENT) {
            $this->ensureAccount(self::CODE_FLOAT_PARENT, $names[self::CODE_FLOAT_PARENT], Account::TYPE_ASSET);

            return $this->ensureAccount($code, $names[$code] ?? 'Float — '.$code, Account::TYPE_ASSET, self::CODE_FLOAT_PARENT);
        }

        return $this->ensureAccount($code, $names[$code], Account::TYPE_ASSET);
    }

    private function ensureAccount(string $code, string $name, string $type, ?string $parentCode = null): Account
    {
        $parentId = null;

        if ($parentCode !== null) {
            $parent = Account::where('code', $parentCode)->first();

            if ($parent === null) {
                $parent = Account::create([
                    'code' => $parentCode,
                    'name' => $parentCode === self::CODE_FLOAT_PARENT ? 'Mobile Money Float' : $parentCode,
                    'type' => Account::TYPE_ASSET,
                    'is_active' => true,
                ]);
            }

            $parentId = $parent->id;
        }

        $account = Account::where('code', $code)->first();

        if ($account !== null) {
            return $account;
        }

        return Account::create([
            'code' => $code,
            'name' => $name,
            'type' => $type,
            'parent_id' => $parentId,
            'is_active' => true,
        ]);
    }

    /**
     * @return array{account_id: int, description: string, debit: float, credit: float}
     */
    private function line(int $accountId, float $debit, float $credit, string $description): array
    {
        return [
            'account_id' => $accountId,
            'description' => $description,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
        ];
    }
}
