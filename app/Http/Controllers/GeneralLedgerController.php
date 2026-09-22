<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\LedgerService;
use App\Services\TransactionJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class GeneralLedgerController extends Controller
{
    public function __invoke(Request $request, LedgerService $ledger): View
    {
        app(TransactionJournalService::class)->ensureSynced();

        $accountId = $request->integer('account_id');
        $account = $accountId > 0 ? Account::find($accountId) : Account::query()->first();

        if ($account === null) {
            return view('finance.general-ledger', [
                'account' => null,
                'selectedAccountId' => 0,
                'ledgerReport' => null,
                'accounts' => collect(),
                'range' => $request->string('range', 'month')->toString(),
            ]);
        }

        $range = $request->string('range', 'month')->toString();
        $from = $this->startOf($range);
        $to = $this->endOf($range);

        $ledgerReport = $ledger->generalLedger($account, $from, $to);

        $accounts = Account::query()
            ->active()
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('finance.general-ledger', compact('account', 'ledgerReport', 'accounts', 'range') + ['selectedAccountId' => $account->id]);
    }

    private function startOf(string $range): ?Carbon
    {
        return match ($range) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(6)->startOfDay(),
            'month' => now()->startOfMonth(),
            default => null,
        };
    }

    private function endOf(string $range): ?Carbon
    {
        return match ($range) {
            'today' => now(),
            '7d' => now(),
            'month' => now(),
            default => null,
        };
    }
}
