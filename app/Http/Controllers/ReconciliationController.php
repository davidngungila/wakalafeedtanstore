<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Reconciliation;
use App\Models\ReconciliationCorrection;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReconciliationController extends Controller
{
    public function index(): View
    {
        $records = Reconciliation::with(['agent', 'reconciler'])->latest()->limit(120)->get();

        $totals = [
            'reconciled' => Reconciliation::where('status', 'reconciled')->count(),
            'open' => Reconciliation::where('status', 'open')->count(),
            'variance' => Reconciliation::where('status', 'variance')->count(),
            'resolved' => Reconciliation::where('status', 'resolved')->count(),
            'varianceAmount' => (float) Reconciliation::where('status', 'variance')->sum('cash_variance'),
        ];

        $networks = Network::orderBy('name')->get();

        $exportColumns = $this->exportColumns();
        $exportRoute = route('reconciliation.export');

        return view('reconciliation.index', compact('records', 'totals', 'networks', 'exportColumns', 'exportRoute'));
    }

    public function export(Request $request, ExportService $export)
    {
        $available = $this->exportColumns();
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = Reconciliation::with(['agent', 'reconciler'])->latest()->limit(5000)->get()
            ->map(fn (Reconciliation $r) => [
                'date' => $r->reconciliation_date,
                'agent' => $r->agent?->code ?? '—',
                'reconciler' => $r->reconciler?->name ?? '—',
                'expected_cash' => money($r->expected_cash),
                'counted_cash' => money($r->counted_cash),
                'cash_variance' => money($r->cash_variance),
                'total_float' => money($r->total_float),
                'float_variance' => money($r->float_variance),
                'status' => ucfirst($r->status),
                'notes' => $r->notes ?? '—',
            ])
            ->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'Reconciliation Report';
        $subtitle = 'Generated '.now()->format('d M Y H:i').' — '.$rows->count().' records';

        if ($format === 'excel') {
            return $export->excel($title, $columns, $rows);
        }

        return $export->pdf($title, $subtitle, $columns, $rows);
    }

    private function exportColumns(): array
    {
        return [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'agent', 'label' => 'Agent'],
            ['key' => 'reconciler', 'label' => 'Reconciled By'],
            ['key' => 'expected_cash', 'label' => 'Expected Cash'],
            ['key' => 'counted_cash', 'label' => 'Counted Cash'],
            ['key' => 'cash_variance', 'label' => 'Cash Variance'],
            ['key' => 'total_float', 'label' => 'Total Float'],
            ['key' => 'float_variance', 'label' => 'Float Variance'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'notes', 'label' => 'Notes'],
        ];
    }

    public function create(): View|RedirectResponse
    {
        $agent = cash_point();

        if ($agent === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first before reconciling.');
        }

        $openingCash = $this->previousClosingCash($agent, today()->toDateString());

        $balances = $agent->balances()->with('network')->orderBy('network_id')->get()->mapWithKeys(
            fn (NetworkBalance $balance): array => [$balance->network_id => (float) $balance->balance],
        );

        return view('reconciliation.create', [
            'agent' => $agent,
            'networks' => Network::orderBy('name')->get(['id', 'name', 'color']),
            'expectedCash' => (float) $agent->cash_balance,
            'openingCash' => $openingCash,
            'balances' => $balances,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reconciliation_date' => ['required', 'date'],
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'counted_floats' => ['nullable', 'array'],
            'counted_floats.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $agent = cash_point();

        if ($agent === null) {
            $message = 'Set up the cash point first before reconciling.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('cash-point.index')->with('error', $message);
        }

        $expectedCash = (float) $agent->cash_balance;
        $countedCash = (float) $validated['counted_cash'];

        $networkBalances = $agent->balances()->with('network')->get()->map(function (NetworkBalance $balance) use ($validated) {
            $counted = $validated['counted_floats'][$balance->network_id] ?? (float) $balance->balance;

            return [
                'network' => $balance->network->name,
                'system' => (float) $balance->balance,
                'counted' => (float) $counted,
            ];
        });

        $totalFloat = (float) $agent->balances()->sum('balance');
        $countedFloatTotal = collect($validated['counted_floats'] ?? [])->sum();

        $floatVariance = round($countedFloatTotal - $totalFloat, 2);

        $status = $countedCash === $expectedCash && $floatVariance === 0.0 ? 'reconciled' : 'variance';

        $record = Reconciliation::create([
            'agent_id' => $agent->id,
            'reconciliation_date' => $validated['reconciliation_date'],
            'opening_cash' => $this->previousClosingCash($agent, $validated['reconciliation_date']),
            'expected_cash' => $expectedCash,
            'counted_cash' => $countedCash,
            'cash_variance' => round($countedCash - $expectedCash, 2),
            'total_float' => $totalFloat,
            'float_variance' => $floatVariance,
            'network_balances' => $networkBalances->toArray(),
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'reconciled_by' => auth()->id(),
        ]);

        if ($status === 'reconciled') {
            $agent->update(['cash_balance' => $countedCash]);
        }

        $this->recordAudit('Reconciliation saved', 'Reconciliation', $record->id, [
            'agent' => $agent->code,
            'variance' => $record->cash_variance,
            'status' => $status,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $status === 'reconciled'
                ? 'Reconciliation matched perfectly.'
                : 'Reconciliation saved with variance.']);
        }

        return back()->with('status', 'Reconciliation saved.');
    }

    public function show(Reconciliation $reconciliation): View
    {
        $reconciliation->load(['agent', 'reconciler', 'corrections.network', 'corrections.creator']);

        $channels = $this->settledChannels($reconciliation);

        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);

        return view('reconciliation.show', compact('reconciliation', 'channels', 'networks'));
    }

    public function storeCorrection(Request $request, Reconciliation $reconciliation): RedirectResponse
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:cash,float'],
            'network_id' => ['nullable', 'required_if:scope,float', 'exists:networks,id'],
            'type' => ['required', Rule::in(array_keys(ReconciliationCorrection::types()))],
            'reference' => ['required', 'string', 'max:120'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $correction = $reconciliation->corrections()->create([
            'scope' => $validated['scope'],
            'network_id' => $validated['scope'] === 'float' ? $validated['network_id'] : null,
            'type' => $validated['type'],
            'reference' => $validated['reference'],
            'amount' => $validated['amount'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        $this->recomputeStatus($reconciliation);

        $this->recordAudit('Reconciliation correction recorded', 'Reconciliation', $reconciliation->id, [
            'type' => $correction->typeLabel(),
            'reference' => $correction->reference,
            'amount' => $correction->amount,
            'status' => $reconciliation->fresh()->status,
        ]);

        return back()->with('status', 'Correction recorded.');
    }

    public function destroyCorrection(Reconciliation $reconciliation, ReconciliationCorrection $correction): RedirectResponse
    {
        $correction->delete();

        $this->recomputeStatus($reconciliation);

        $this->recordAudit('Reconciliation correction removed', 'Reconciliation', $reconciliation->id, [
            'reference' => $correction->reference,
            'status' => $reconciliation->fresh()->status,
        ]);

        return back()->with('status', 'Correction removed.');
    }

    /**
     * Per-channel settlement view: raw variance, settled by corrections, remaining.
     *
     * @return array<string, array{
     *     label: string,
     *     system: float,
     *     counted: float,
     *     variance: float,
     *     settled: float,
     *     remaining: float
     * }>
     */
    private function settledChannels(Reconciliation $reconciliation): array
    {
        $corrections = $reconciliation->corrections;

        $channels = [
            'cash' => [
                'label' => 'Cash in Till',
                'system' => (float) $reconciliation->expected_cash,
                'counted' => (float) $reconciliation->counted_cash,
                'variance' => (float) $reconciliation->cash_variance,
            ],
        ];

        foreach (($reconciliation->network_balances ?? []) as $balance) {
            $name = $balance['network'] ?? 'Network';
            $system = (float) ($balance['system'] ?? 0);
            $counted = (float) ($balance['counted'] ?? 0);
            $channels['float.'.$name] = [
                'label' => $name.' Float',
                'system' => $system,
                'counted' => $counted,
                'variance' => $counted - $system,
            ];
        }

        foreach ($channels as $key => $channel) {
            $sum = 0.0;

            if ($key === 'cash') {
                $sum = (float) $corrections->where('scope', 'cash')->sum(fn (ReconciliationCorrection $c) => $c->signedAmount());
            } else {
                $networkName = substr($key, 6);
                $sum = (float) $corrections
                    ->where('scope', 'float')
                    ->filter(fn (ReconciliationCorrection $c) => $c->network?->name === $networkName)
                    ->sum(fn (ReconciliationCorrection $c) => $c->signedAmount());
            }

            $channels[$key]['settled'] = round($sum, 2);
            $channels[$key]['remaining'] = round($channel['variance'] - $sum, 2);
        }

        return $channels;
    }

    private function recomputeStatus(Reconciliation $reconciliation): void
    {
        $channels = $this->settledChannels($reconciliation);

        $anyVariance = collect($channels)->contains(fn (array $c) => abs($c['variance']) > 0.005);
        $allResolved = collect($channels)->every(fn (array $c) => abs($c['remaining']) < 0.005);

        $status = ! $anyVariance ? 'reconciled' : ($allResolved ? 'resolved' : 'variance');

        if ($reconciliation->status !== $status) {
            $reconciliation->update(['status' => $status]);
        }
    }

    private function previousClosingCash(Agent $agent, string $date): float
    {
        $previous = $agent->reconciliations()
            ->where('reconciliation_date', '<', $date)
            ->orderByDesc('reconciliation_date')
            ->first();

        return $previous ? (float) $previous->counted_cash : (float) $agent->cash_balance;
    }
}
