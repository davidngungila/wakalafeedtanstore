<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Services\ExportService;
use App\Services\TransactionJournalService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChartOfAccountsController extends Controller
{
    /**
     * List the chart of accounts with their current balances.
     */
    public function index(): View
    {
        app(TransactionJournalService::class)->ensureSynced();

        $accounts = Account::query()
            ->withCount('journalLines')
            ->orderBy('code')
            ->get();

        $tree = $accounts
            ->reject(fn (Account $account) => $account->parent_id !== null)
            ->map(fn (Account $account) => $this->node($account, $accounts))
            ->values();

        $totals = $accounts->groupBy('type')->map->count();

        $exportColumns = $this->exportColumns();
        $exportRoute = route('finance.accounts.export');

        return view('finance.chart-of-accounts', compact('tree', 'accounts', 'totals', 'exportColumns', 'exportRoute'));
    }

    public function export(Request $request, ExportService $export)
    {
        $available = $this->exportColumns();
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = Account::orderBy('code')->get()
            ->map(fn (Account $a) => [
                'code' => $a->code,
                'name' => $a->name,
                'type' => ucfirst($a->type),
                'parent' => $a->parent_id ? ($a->parent?->code ?? '—') : '—',
                'description' => $a->description ?? '—',
                'status' => $a->is_active ? 'Active' : 'Inactive',
                'journal_lines' => $a->journalLines()->count(),
            ])
            ->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'Chart of Accounts';
        $subtitle = 'Generated '.now()->format('d M Y H:i').' — '.$rows->count().' accounts';

        if ($format === 'excel') {
            return $export->excel($title, $columns, $rows);
        }

        return $export->pdf($title, $subtitle, $columns, $rows);
    }

    private function exportColumns(): array
    {
        return [
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'parent', 'label' => 'Parent'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'journal_lines', 'label' => 'Journal Lines'],
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:chart_of_accounts,code'],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:'.implode(',', [
                Account::TYPE_ASSET,
                Account::TYPE_LIABILITY,
                Account::TYPE_EQUITY,
                Account::TYPE_INCOME,
                Account::TYPE_EXPENSE,
            ])],
            'parent_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $account = Account::create([
            ...$validated,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $this->recordAudit('Chart of account created', 'Account', $account->id, ['code' => $account->code]);

        return back()->with('status', 'Account '.$account->code.' created.');
    }

    public function update(Request $request, Account $account): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:chart_of_accounts,code,'.$account->id],
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'in:'.implode(',', [
                Account::TYPE_ASSET,
                Account::TYPE_LIABILITY,
                Account::TYPE_EQUITY,
                Account::TYPE_INCOME,
                Account::TYPE_EXPENSE,
            ])],
            'parent_id' => ['nullable', 'exists:chart_of_accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $account->update([...$validated, 'is_active' => $request->boolean('is_active')]);

        $this->recordAudit('Chart of account updated', 'Account', $account->id, ['code' => $account->code]);

        return back()->with('status', 'Account '.$account->code.' updated.');
    }

    public function destroy(Account $account): RedirectResponse
    {
        if ($account->journalLines()->exists()) {
            return back()->withErrors(['account' => 'Cannot delete: journal lines reference this account.']);
        }

        $code = $account->code;
        $account->delete();

        $this->recordAudit('Chart of account deleted', 'Account', null, ['code' => $code]);

        return back()->with('status', 'Account '.$code.' deleted.');
    }

    /**
     * Recursively build a flat list ready for the tree view.
     *
     * @param  Collection<int, Account>  $all
     * @return array<string, mixed>
     */
    private function node(Account $account, $all): array
    {
        $children = $all
            ->filter(fn (Account $candidate) => $candidate->parent_id === $account->id)
            ->map(fn (Account $child) => $this->node($child, $all))
            ->values()
            ->all();

        return [
            'id' => $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'type' => $account->type,
            'parent_id' => $account->parent_id,
            'description' => $account->description,
            'is_active' => $account->is_active,
            'line_count' => $account->journal_lines_count,
            'children' => $children,
        ];
    }
}
