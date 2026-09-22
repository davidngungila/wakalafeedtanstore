<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\ExportService;
use App\Services\LedgerService;
use App\Services\TransactionJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\View\View;

class GeneralLedgerController extends Controller
{
    public function __invoke(Request $request, LedgerService $ledger): View
    {
        app(TransactionJournalService::class)->ensureSynced();

        $account = $this->resolveAccount($request->input('account_id'));

        if ($account === null) {
            $account = Account::query()->first();
        }

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

        // Provide encrypted ids for the view so URLs stay encrypted
        $accountsForView = $accounts->map(function (Account $a) {
            return [
                'id' => $a->id,
                'encrypted' => $a->getRouteKey(),
                'code' => $a->code,
                'name' => $a->name,
            ];
        });

        $selectedEncrypted = $account ? $account->getRouteKey() : '';

        $exportColumns = $this->exportColumns();
        $exportRoute = route('finance.ledger.export');

        return view('finance.general-ledger', compact('account', 'ledgerReport', 'accounts', 'range', 'accountsForView', 'selectedEncrypted', 'exportColumns', 'exportRoute') + ['selectedAccountId' => $account->id]);
    }

    public function export(Request $request, ExportService $export)
    {
        app(TransactionJournalService::class)->ensureSynced();

        $account = $this->resolveAccount($request->input('account_id'));

        if ($account === null) {
            $account = Account::query()->first();
        }

        if ($account === null) {
            return back()->with('error', 'No accounts found.');
        }

        $range = $request->string('range', 'month')->toString();
        $from = $this->startOf($range);
        $to = $this->endOf($range);

        $ledger = app(LedgerService::class)->generalLedger($account, $from, $to);

        $available = $this->exportColumns();
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = collect($ledger['rows'] ?? [])->map(fn (array $row) => [
            'date' => $row['date'] ?? '—',
            'description' => $row['description'] ?? '—',
            'reference' => $row['reference'] ?? '—',
            'debit' => isset($row['debit']) ? money($row['debit']) : '—',
            'credit' => isset($row['credit']) ? money($row['credit']) : '—',
            'balance' => isset($row['balance']) ? money($row['balance']) : '—',
        ])->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'General Ledger — '.$account->code.' '.$account->name;
        $subtitle = 'Period: '.ucfirst($range).' — Generated '.now()->format('d M Y H:i');

        if ($format === 'excel') {
            return $export->excel($title, $columns, $rows);
        }

        return $export->pdf($title, $subtitle, $columns, $rows);
    }

    private function exportColumns(): array
    {
        return [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'debit', 'label' => 'Debit'],
            ['key' => 'credit', 'label' => 'Credit'],
            ['key' => 'balance', 'label' => 'Balance'],
        ];
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

    private function resolveAccount(mixed $value): ?Account
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        // Try encrypted first
        try {
            $id = (int) Crypt::decryptString((string) $value);
            $found = Account::find($id);
            if ($found) {
                return $found;
            }
        } catch (\Throwable) {
            // fall through to numeric check
        }

        if (is_numeric($value)) {
            return Account::find((int) $value);
        }

        return null;
    }
}
