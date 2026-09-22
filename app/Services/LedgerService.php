<?php

namespace App\Services;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Computes accounting balances and reports from posted journal entries.
 *
 * Every figure in the finance module is derived from journal entry lines, so a
 * single source of truth drives both the general ledger and the statements.
 */
class LedgerService
{
    /**
     * All posted lines joined with their entry header, optionally restricted to
     * a posting date range and a single account.
     *
     * @return Collection<int, JournalEntryLine>
     */
    public function postedLines(?Carbon $from = null, ?Carbon $to = null, ?int $accountId = null): Collection
    {
        return JournalEntryLine::query()
            ->with(['entry', 'account'])
            ->whereHas('entry', function ($query) use ($from, $to) {
                $query->where('status', JournalEntry::STATUS_POSTED);

                if ($from !== null) {
                    $query->where('entry_date', '>=', $from->toDateString());
                }

                if ($to !== null) {
                    $query->where('entry_date', '<', $to->copy()->addDay()->toDateString());
                }
            })
            ->when($accountId !== null, fn ($query) => $query->where('account_id', $accountId))
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Net signed movement per account for the given lines.
     *
     * @param  Collection<int, JournalEntryLine>  $lines
     * @return array<int, float>
     */
    public function netMovementPerAccount(Collection $lines): array
    {
        $totals = [];

        foreach ($lines as $line) {
            $accountId = $line->account_id;

            $totals[$accountId] = ($totals[$accountId] ?? 0) + $line->rawDelta();
        }

        return $totals;
    }

    /**
     * Balance per account: net movement normalised by each account's normal
     * balance side so every balance reads as a positive "value". Negative
     * values indicate the account sits on the opposite side.
     *
     * @param  Collection<int, JournalEntryLine>  $lines
     * @return array<int, float>
     */
    public function balancesByAccount(Collection $lines, bool $onlyActive = true): array
    {
        $movement = $this->netMovementPerAccount($lines);

        $accounts = Account::query()
            ->when($onlyActive, fn ($query) => $query->where('is_active', true))
            ->whereIn('id', array_keys($movement))
            ->get()
            ->keyBy('id');

        $balances = [];

        foreach ($movement as $accountId => $delta) {
            $account = $accounts->get($accountId);

            if ($account === null) {
                continue;
            }

            $balances[$accountId] = $account->isDebitNormal() ? $delta : -$delta;
        }

        return $balances;
    }

    /**
     * Income statement for the period bounded by $from/$to.
     *
     * Revenue accounts have a credit normal balance; expense accounts a debit
     * normal balance. Net income is revenue minus expenses.
     *
     * @return array{
     *     from: Carbon,
     *     to: Carbon,
     *     revenue: array<int, array{code: string, name: string, amount: float}>,
     *     expenses: array<int, array{code: string, name: string, amount: float}>,
     *     revenueTotal: float,
     *     expenseTotal: float,
     *     netIncome: float,
     * }
     */
    public function incomeStatement(Carbon $from, Carbon $to): array
    {
        $lines = $this->postedLines($from, $to->endOfDay());

        $revenue = [];
        $expenses = [];
        $revenueTotal = 0.0;
        $expenseTotal = 0.0;

        foreach ($this->balancesByAccount($lines) as $accountId => $balance) {
            $account = Account::query()->find($accountId);

            if ($account === null) {
                continue;
            }

            if ($account->type === Account::TYPE_INCOME) {
                $revenueTotal += $balance;
                $revenue[] = ['code' => $account->code, 'name' => $account->name, 'amount' => $balance];

                continue;
            }

            if ($account->type === Account::TYPE_EXPENSE) {
                $expenseTotal += $balance;
                $expenses[] = ['code' => $account->code, 'name' => $account->name, 'amount' => $balance];
            }
        }

        usort($revenue, fn (array $a, array $b) => $a['code'] <=> $b['code']);
        usort($expenses, fn (array $a, array $b) => $a['code'] <=> $b['code']);

        return [
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'revenueTotal' => $revenueTotal,
            'expenseTotal' => $expenseTotal,
            'netIncome' => $revenueTotal - $expenseTotal,
        ];
    }

    /**
     * Balance sheet as of $asOf (end of day), including the current period's
     * retained earnings so assets always equal liabilities plus equity.
     *
     * @return array{
     *     asOf: Carbon,
     *     assets: array<int, array{code: string, name: string, amount: float}>,
     *     liabilities: array<int, array{code: string, name: string, amount: float}>,
     *     equity: array<int, array{code: string, name: string, amount: float}>,
     *     assetTotal: float,
     *     liabilityTotal: float,
     *     equityTotal: float,
     *     netIncome: float,
     * }
     */
    public function balanceSheet(?Carbon $asOf = null): array
    {
        $asOf ??= today();

        $lines = $this->postedLines(null, $asOf->endOfDay());

        $assets = [];
        $liabilities = [];
        $equity = [];
        $assetTotal = 0.0;
        $liabilityTotal = 0.0;
        $equityTotal = 0.0;

        foreach ($this->balancesByAccount($lines) as $accountId => $balance) {
            $account = Account::query()->find($accountId);

            if ($account === null) {
                continue;
            }

            $entry = ['code' => $account->code, 'name' => $account->name, 'amount' => $balance];

            match ($account->type) {
                Account::TYPE_ASSET => $this->pushTo($assets, $entry, $assetTotal),
                Account::TYPE_LIABILITY => $this->pushTo($liabilities, $entry, $liabilityTotal),
                Account::TYPE_EQUITY => $this->pushTo($equity, $entry, $equityTotal),
                default => null,
            };
        }

        $netIncome = 0.0;

        foreach ($this->balancesByAccount($lines) as $accountId => $balance) {
            $account = Account::query()->find($accountId);

            if ($account === null) {
                continue;
            }

            if ($account->type === Account::TYPE_INCOME) {
                $netIncome += $balance;
            } elseif ($account->type === Account::TYPE_EXPENSE) {
                $netIncome -= $balance;
            }
        }

        usort($assets, fn (array $a, array $b) => $a['code'] <=> $b['code']);
        usort($liabilities, fn (array $a, array $b) => $a['code'] <=> $b['code']);
        usort($equity, fn (array $a, array $b) => $a['code'] <=> $b['code']);

        return [
            'asOf' => $asOf,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'assetTotal' => $assetTotal,
            'liabilityTotal' => $liabilityTotal,
            'equityTotal' => $equityTotal,
            'netIncome' => $netIncome,
        ];
    }

    /**
     * General ledger rows for a single account across a period, with opening
     * and running balances.
     *
     * @return array{
     *     account: Account,
     *     from: ?Carbon,
     *     to: ?Carbon,
     *     opening: float,
     *     rows: array<int, array{date: string, reference: string, description: string, debit: float, credit: float, balance: float}>,
     *     closing: float,
     * }
     */
    public function generalLedger(Account $account, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $openingLines = $this->postedLines(null, $from !== null ? $from->copy()->subDay()->endOfDay() : null, $account->id);
        $opening = $this->signedBalance($account, $openingLines);

        $rows = [];
        $running = $opening;

        foreach ($this->postedLines($from, $to !== null ? $to->endOfDay() : null, $account->id) as $line) {
            $running += $line->signedAmount();
            $rows[] = [
                'date' => $line->entry->entry_date->toDateString(),
                'reference' => $line->entry->reference,
                'entry_id' => $line->entry->id,
                'description' => $line->description ?: $line->entry->description,
                'debit' => (float) $line->debit,
                'credit' => (float) $line->credit,
                'balance' => $running,
            ];
        }

        return [
            'account' => $account,
            'from' => $from,
            'to' => $to,
            'opening' => $opening,
            'rows' => $rows,
            'closing' => $running,
        ];
    }

    /**
     * Opening balance for a single account from lines before the period.
     *
     * @param  Collection<int, JournalEntryLine>  $lines
     */
    public function signedBalance(Account $account, Collection $lines): float
    {
        $onOpening = 0.0;

        foreach ($lines as $line) {
            $onOpening += $line->signedAmount();
        }

        return $onOpening;
    }

    /**
     * @param  array<int, array<string, mixed>>  $bucket
     */
    private function pushTo(array &$bucket, array $entry, float &$total): void
    {
        $bucket[] = $entry;
        $total += $entry['amount'];
    }
}
