<?php

namespace App\Http\Controllers;

use App\Services\LedgerService;
use App\Services\TransactionJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class FinancialStatementController extends Controller
{
    public function income(Request $request, LedgerService $ledger): View
    {
        app(TransactionJournalService::class)->ensureSynced();

        [$from, $to] = $this->period($request);

        $statement = $ledger->incomeStatement($from, $to);

        return view('finance.income-statement', compact('statement'));
    }

    public function balance(Request $request, LedgerService $ledger): View
    {
        app(TransactionJournalService::class)->ensureSynced();

        $asOf = $request->filled('as_of')
            ? Carbon::parse($request->string('as_of')->toString())
            : today();

        $sheet = $ledger->balanceSheet($asOf);

        return view('finance.balance-sheet', compact('sheet'));
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function period(Request $request): array
    {
        $range = $request->string('range', 'month')->toString();

        return match ($range) {
            'today' => [now()->startOfDay(), now()],
            '7d' => [now()->subDays(6)->startOfDay(), now()],
            'month' => [now()->startOfMonth(), now()],
            default => [now()->startOfYear(), now()],
        };
    }
}
