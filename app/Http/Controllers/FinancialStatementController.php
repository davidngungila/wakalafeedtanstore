<?php

namespace App\Http\Controllers;

use App\Services\ExportService;
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

    public function exportIncome(Request $request, ExportService $export, LedgerService $ledger)
    {
        app(TransactionJournalService::class)->ensureSynced();

        [$from, $to] = $this->period($request);
        $statement = $ledger->incomeStatement($from, $to);

        $available = [
            ['key' => 'account', 'label' => 'Account'],
            ['key' => 'debit', 'label' => 'Debit'],
            ['key' => 'credit', 'label' => 'Credit'],
            ['key' => 'net', 'label' => 'Net'],
        ];
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = collect($statement['rows'] ?? [])->map(fn (array $r) => [
            'account' => ($r['code'] ?? '').' '.($r['name'] ?? ''),
            'debit' => isset($r['debit']) ? money($r['debit']) : '—',
            'credit' => isset($r['credit']) ? money($r['credit']) : '—',
            'net' => isset($r['net']) ? money($r['net']) : '—',
        ])->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'Income Statement';
        $subtitle = 'Period: '.$from->format('d M Y').' — '.$to->format('d M Y');

        if ($format === 'excel') {
            return $export->excel($title, $columns, $rows);
        }

        return $export->pdf($title, $subtitle, $columns, $rows);
    }

    public function exportBalance(Request $request, ExportService $export, LedgerService $ledger)
    {
        app(TransactionJournalService::class)->ensureSynced();

        $asOf = $request->filled('as_of')
            ? Carbon::parse($request->string('as_of')->toString())
            : today();

        $sheet = $ledger->balanceSheet($asOf);

        $available = [
            ['key' => 'account', 'label' => 'Account'],
            ['key' => 'debit', 'label' => 'Debit'],
            ['key' => 'credit', 'label' => 'Credit'],
            ['key' => 'net', 'label' => 'Net'],
        ];
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = collect($sheet['rows'] ?? [])->map(fn (array $r) => [
            'account' => ($r['code'] ?? '').' '.($r['name'] ?? ''),
            'debit' => isset($r['debit']) ? money($r['debit']) : '—',
            'credit' => isset($r['credit']) ? money($r['credit']) : '—',
            'net' => isset($r['net']) ? money($r['net']) : '—',
        ])->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'Balance Sheet';
        $subtitle = 'As of '.$asOf->format('d M Y');

        if ($format === 'excel') {
            return $export->excel($title, $columns, $rows);
        }

        return $export->pdf($title, $subtitle, $columns, $rows);
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
