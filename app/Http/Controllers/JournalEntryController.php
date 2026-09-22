<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Transaction;
use App\Services\ExportService;
use App\Services\TransactionJournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class JournalEntryController extends Controller
{
    public function index(Request $request): View
    {
        app(TransactionJournalService::class)->ensureSynced();

        $status = $request->string('status', 'all')->toString();

        $entries = JournalEntry::query()
            ->with('lines.account')
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $totals = [
            'drafts' => JournalEntry::where('status', JournalEntry::STATUS_DRAFT)->count(),
            'posted' => JournalEntry::where('status', JournalEntry::STATUS_POSTED)->count(),
            'total' => JournalEntry::count(),
        ];

        $accounts = Account::query()
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'parent_id']);

        $exportColumns = $this->exportColumns();
        $exportRoute = route('finance.journals.export');

        return view('finance.journal-entries', compact('entries', 'totals', 'accounts', 'status', 'exportColumns', 'exportRoute'));
    }

    public function export(Request $request, ExportService $export)
    {
        $status = $request->string('status', 'all')->toString();

        $available = $this->exportColumns();
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = JournalEntry::query()
            ->with('lines.account')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->limit(5000)
            ->get()
            ->map(fn (JournalEntry $e) => [
                'reference' => $e->reference,
                'date' => $e->entry_date,
                'description' => $e->description,
                'status' => ucfirst($e->status),
                'debits' => money($e->lines()->sum('debit')),
                'credits' => money($e->lines()->sum('credit')),
                'created_by' => $e->creator?->name ?? '—',
                'posted_at' => $e->posted_at?->format('d M Y H:i') ?? '—',
            ])
            ->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'Journal Entries';
        $subtitle = 'Generated '.now()->format('d M Y H:i').' — '.$rows->count().' entries';

        if ($format === 'excel') {
            return $export->excel($title, $columns, $rows);
        }

        return $export->pdf($title, $subtitle, $columns, $rows);
    }

    private function exportColumns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'debits', 'label' => 'Debits'],
            ['key' => 'credits', 'label' => 'Credits'],
            ['key' => 'created_by', 'label' => 'Created By'],
            ['key' => 'posted_at', 'label' => 'Posted At'],
        ];
    }

    public function show(JournalEntry $journalEntry): View
    {
        app(TransactionJournalService::class)->ensureSynced();

        $journalEntry->load(['lines.account', 'creator', 'poster']);

        $transaction = Transaction::with(['network', 'agent', 'operator'])
            ->where('reference', $journalEntry->reference)
            ->first();

        // Find reversal entry if this entry was reversed, or original if this is a reversal
        $reversal = null;
        $original = null;

        if ($journalEntry->status === JournalEntry::STATUS_REVERSED) {
            $reversal = JournalEntry::with('lines.account')
                ->where('description', 'like', 'Reversal of '.$journalEntry->reference.'%')
                ->first();
        }

        if (str_starts_with($journalEntry->reference, 'RVS-')) {
            // Try to extract original reference from description: "Reversal of TXN-... — ..."
            if (preg_match('/Reversal of ([A-Z0-9\-]+)/', $journalEntry->description, $m)) {
                $original = JournalEntry::with('lines.account')->where('reference', $m[1])->first();
            }
        }

        // Also check if any entry reverses this one via lines (fallback)
        if ($reversal === null && $journalEntry->status === JournalEntry::STATUS_REVERSED) {
            $reversal = JournalEntry::where('reference', 'like', 'RVS-%')
                ->where('description', 'like', '%'.$journalEntry->reference.'%')
                ->first();
        }

        return view('finance.journal-entry-show', compact('journalEntry', 'transaction', 'reversal', 'original'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:40', 'unique:journal_entries,reference'],
            'status' => ['sometimes', 'in:'.implode(',', [
                JournalEntry::STATUS_DRAFT,
                JournalEntry::STATUS_POSTED,
            ])],
            'lines' => ['required', 'array', 'min:2', 'max:50'],
            'lines.*.account_id' => ['required', 'exists:chart_of_accounts,id'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.debit' => ['required_without:lines.*.credit', 'numeric', 'min:0'],
            'lines.*.credit' => ['required_without:lines.*.debit', 'numeric', 'min:0'],
        ]);

        $lines = collect($validated['lines'])
            ->filter(fn (array $line) => (float) $line['debit'] > 0 || (float) $line['credit'] > 0)
            ->values();

        if ($lines->count() < 2) {
            return back()->withErrors(['lines' => 'A journal entry needs at least two non-empty lines.']);
        }

        $debits = (float) $lines->sum('debit');
        $credits = (float) $lines->sum('credit');

        if (abs($debits - $credits) > 0.01) {
            return back()->withErrors(['lines' => sprintf('Debits (%s) must equal credits (%s).', money($debits), money($credits))]);
        }

        $reference = $validated['reference']
            ?? 'JE-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

        while (JournalEntry::where('reference', $reference)->exists()) {
            $reference = 'JE-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        }

        $status = $validated['status'] ?? JournalEntry::STATUS_DRAFT;
        $postNow = $status === JournalEntry::STATUS_POSTED;

        $entry = DB::transaction(function () use ($validated, $lines, $reference, $postNow, $debits, $credits): JournalEntry {
            $entry = JournalEntry::create([
                'entry_date' => $validated['entry_date'],
                'reference' => $reference,
                'description' => trim($validated['description']),
                'status' => $postNow ? JournalEntry::STATUS_POSTED : JournalEntry::STATUS_DRAFT,
                'created_by' => auth()->id(),
                'posted_by' => $postNow ? auth()->id() : null,
                'posted_at' => $postNow ? now() : null,
            ]);

            foreach ($lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line['account_id'],
                    'description' => isset($line['description']) && $line['description'] !== '' ? $line['description'] : null,
                    'debit' => (float) $line['debit'],
                    'credit' => (float) $line['credit'],
                ]);
            }

            if ($postNow) {
                $this->recordAudit('Journal entry posted', 'JournalEntry', $entry->id, [
                    'reference' => $reference,
                    'debits' => $debits,
                    'credits' => $credits,
                ]);
            }

            return $entry;
        });

        if (! $postNow) {
            $this->recordAudit('Journal entry drafted', 'JournalEntry', $entry->id, ['reference' => $reference]);
        }

        return back()->with('status', 'Journal entry '.$reference.' '.($postNow ? 'posted' : 'saved as draft').'.');
    }

    public function post(JournalEntry $journalEntry): RedirectResponse
    {
        abort_if($journalEntry->status !== JournalEntry::STATUS_DRAFT, 409);

        $debits = $journalEntry->lines()->sum('debit');
        $credits = $journalEntry->lines()->sum('credit');

        if (abs($debits - $credits) > 0.01) {
            return back()->withErrors(['entry' => sprintf('Cannot post: debits (%s) do not equal credits (%s).', money($debits), money($credits))]);
        }

        $journalEntry->update([
            'status' => JournalEntry::STATUS_POSTED,
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        $this->recordAudit('Journal entry posted', 'JournalEntry', $journalEntry->id, ['reference' => $journalEntry->reference]);

        return back()->with('status', 'Journal entry '.$journalEntry->reference.' posted.');
    }

    public function reverse(JournalEntry $journalEntry): RedirectResponse
    {
        abort_if($journalEntry->status !== JournalEntry::STATUS_POSTED, 409);

        $reverseAmount = $journalEntry->lines()->first();
        $entry = DB::transaction(function () use ($journalEntry): JournalEntry {
            $newReference = 'RVS-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

            while (JournalEntry::where('reference', $newReference)->exists()) {
                $newReference = 'RVS-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            }

            $entry = JournalEntry::create([
                'entry_date' => today(),
                'reference' => $newReference,
                'description' => 'Reversal of '.$journalEntry->reference.' — '.$journalEntry->description,
                'status' => JournalEntry::STATUS_POSTED,
                'created_by' => auth()->id(),
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ]);

            foreach ($journalEntry->lines as $line) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $line->account_id,
                    'description' => 'Reversal of '.$line->description,
                    'debit' => (float) $line->credit,
                    'credit' => (float) $line->debit,
                ]);
            }

            $journalEntry->update(['status' => JournalEntry::STATUS_REVERSED]);

            $this->recordAudit('Journal entry reversed', 'JournalEntry', $entry->id, ['reversed_reference' => $journalEntry->reference]);

            return $entry;
        });

        return back()->with('status', 'Journal entry '.$journalEntry->reference.' reversed as '.$entry->reference.'.');
    }
}
