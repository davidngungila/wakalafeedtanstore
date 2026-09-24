<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\DailyOpening;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\Reconciliation;
use App\Models\ReconciliationCorrection;
use App\Models\Transaction;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

    public function create(Request $request): View|RedirectResponse
    {
        $agent = cash_point();

        if ($agent === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first before reconciling.');
        }

        $isAdmin = is_admin();
        $rawDate = $request->input('date', $request->input('reconciliation_date', today()->toDateString()));

        try {
            $viewDate = Carbon::parse($rawDate)->toDateString();
        } catch (\Throwable) {
            $viewDate = today()->toDateString();
        }

        // Non-admin can only reconcile today
        if (! $isAdmin) {
            $viewDate = today()->toDateString();
        }

        // Disallow future dates
        if ($viewDate > today()->toDateString()) {
            $viewDate = today()->toDateString();
        }

        $run = $this->buildRun($agent, $viewDate);

        $existing = Reconciliation::where('agent_id', $agent->id)
            ->where('reconciliation_date', $viewDate)
            ->latest()
            ->first();

        $availableDates = DailyOpening::where('agent_id', $agent->id)
            ->orderByDesc('opening_date')
            ->limit(30)
            ->pluck('opening_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->values();

        // Load all opening data + transactions done for the selected date (admin request)
        $dayOpening = DailyOpening::forAgentAndDate($agent->id, Carbon::parse($viewDate))->first();
        $dayTransactions = Transaction::with(['network', 'operator', 'agent'])
            ->where('agent_id', $agent->id)
            ->whereDate('created_at', $viewDate)
            ->latest()
            ->limit(100)
            ->get();

        $dayFloatTransactions = FloatTransaction::with(['network', 'operator'])
            ->where('agent_id', $agent->id)
            ->whereDate('created_at', $viewDate)
            ->latest()
            ->limit(50)
            ->get();

        return view('reconciliation.create', [
            'agent' => $agent,
            'run' => $run,
            'existing' => $existing,
            'selectedDate' => $viewDate,
            'isAdmin' => $isAdmin,
            'availableDates' => $availableDates,
            'dayOpening' => $dayOpening,
            'dayTransactions' => $dayTransactions,
            'dayFloatTransactions' => $dayFloatTransactions,
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

        $run = $this->buildRun($agent, $validated['reconciliation_date']);

        $expectedCash = $run['expectedCash'];
        $countedCash = (float) $validated['counted_cash'];

        $networkBalances = collect($run['networks'])->map(function (array $row) use ($validated): array {
            $counted = (float) ($validated['counted_floats'][$row['id']] ?? $row['expected']);

            return [
                'network_id' => $row['id'],
                'network' => $row['name'],
                'opening' => $row['opening'],
                'deposits' => $row['deposits'],
                'withdrawals' => $row['withdrawals'],
                'expected' => $row['expected'],
                'system' => $row['expected'],
                'counted' => $counted,
                'variance' => round($counted - $row['expected'], 2),
            ];
        })->values()->all();

        $countedFloatTotal = (float) collect($networkBalances)->sum('counted');

        $cashVariance = round($countedCash - $expectedCash, 2);
        $floatVariance = round($countedFloatTotal - $run['expectedFloat'], 2);

        $tieOut = round(($run['openingCash'] + $run['openingFloat']) - ($countedCash + $countedFloatTotal), 2);

        $status = abs($cashVariance) < 0.005 && abs($floatVariance) < 0.005 && abs($tieOut) < 0.005
            ? 'reconciled'
            : 'variance';

        $record = Reconciliation::create([
            'agent_id' => $agent->id,
            'reconciliation_date' => $validated['reconciliation_date'],
            'opening_cash' => $run['openingCash'],
            'cash_deposits' => $run['cashDeposits'],
            'cash_withdrawals' => $run['cashWithdrawals'],
            'expected_cash' => $expectedCash,
            'opening_float' => $run['openingFloat'],
            'counted_cash' => $countedCash,
            'cash_variance' => $cashVariance,
            'total_float' => $run['expectedFloat'],
            'float_variance' => $floatVariance,
            'tie_out' => $tieOut,
            'network_balances' => $networkBalances,
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'reconciled_by' => auth()->id(),
        ]);

        if ($status === 'reconciled') {
            $agent->update(['cash_balance' => $countedCash]);

            foreach ($networkBalances as $row) {
                $balance = $agent->balances()->where('network_id', $row['network_id'])->first();
                if ($balance !== null) {
                    $balance->update(['balance' => $row['counted']]);
                }
            }
        }

        $this->recordAudit('Reconciliation saved', 'Reconciliation', $record->id, [
            'agent' => $agent->code,
            'opening_cash' => $run['openingCash'],
            'deposits' => $run['cashDeposits'],
            'withdrawals' => $run['cashWithdrawals'],
            'variance' => $record->cash_variance,
            'tie_out' => $tieOut,
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

        $run = $this->runFromRecord($reconciliation);

        return view('reconciliation.show', compact('reconciliation', 'channels', 'networks', 'run'));
    }

    public function createCorrection(Reconciliation $reconciliation): View
    {
        $reconciliation->load(['agent']);

        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);

        return view('reconciliation.corrections.create', compact('reconciliation', 'networks'));
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

        return redirect()->route('reconciliation.show', $reconciliation)->with('status', 'Correction recorded.');
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
     * Build the reconciliation run for an agent on a date.
     *
     * Float per network: expected closing = opening float + withdrawals − deposits.
     * Cash: expected closing = opening cash + deposits − withdrawals (all networks).
     *
     * Opening values come from the day's Daily Opening when one exists, otherwise
     * from the recorded opening balances / previous closing cash.
     *
     * @return array{
     *     date: string,
     *     openingCash: float,
     *     cashDeposits: float,
     *     cashWithdrawals: float,
     *     expectedCash: float,
     *     openingFloat: float,
     *     expectedFloat: float,
     *     networks: array<int, array{
     *         id: int,
     *         name: string,
     *         color: string,
     *         opening: float,
     *         deposits: float,
     *         withdrawals: float,
     *         expected: float,
     *     }>,
     * }
     */
    private function buildRun(Agent $agent, string $date): array
    {
        // Show float opening for ALL networks (active + inactive) as requested
        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);

        $dailyOpening = DailyOpening::forAgentAndDate($agent->id, Carbon::parse($date))->first();

        $transactions = Transaction::query()
            ->where('agent_id', $agent->id)
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->get();

        // Per user request: float_topup and float_deposit (deposit float) must be ADDED to float (increase), not subtracted
        // Use same deltas as TransactionService for accuracy, but group float_topup/float_deposit as float IN
        $depositsByNetwork = $transactions
            ->whereIn('type', ['deposit'])
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum('amount'));

        $withdrawalsByNetwork = $transactions
            ->where('type', 'withdrawal')
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum('amount'));

        $floatTopupsByNetwork = $transactions
            ->whereIn('type', ['float_topup', 'float_deposit'])
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum('amount'));

        $bankToWalletByNetwork = $transactions
            ->where('type', 'bank_to_wallet')
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum('amount'));

        $balances = $agent->balances()->get()->keyBy('network_id');

        $rows = $networks->map(function (Network $network) use ($dailyOpening, $balances, $depositsByNetwork, $withdrawalsByNetwork, $floatTopupsByNetwork, $bankToWalletByNetwork): array {
            $balance = $balances->get($network->id);

            $opening = $dailyOpening !== null
                ? $dailyOpening->getFloatOpening($network->id)
                : (float) ($balance?->opening_balance ?? 0);

            if ($opening == 0.0) {
                $opening = (float) ($balance?->balance ?? 0);
            }

            $deposits = (float) ($depositsByNetwork[$network->id] ?? 0);
            $withdrawals = (float) ($withdrawalsByNetwork[$network->id] ?? 0);
            $floatTopups = (float) ($floatTopupsByNetwork[$network->id] ?? 0);
            $bankIns = (float) ($bankToWalletByNetwork[$network->id] ?? 0);

            // Float expected: opening - customer deposits (float out) + withdrawals (float in) + float topups/deposits (float in) + bank_to_wallet (float in)
            // Per user: float_topup and float_deposit must be ADDED to float
            $expected = round($opening - $deposits + $withdrawals + $floatTopups + $bankIns, 2);

            return [
                'id' => $network->id,
                'name' => $network->name,
                'color' => $network->color,
                'opening' => $opening,
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'float_topups' => $floatTopups,
                'bank_ins' => $bankIns,
                'expected' => $expected,
            ];
        })->values()->all();

        $openingCash = $dailyOpening !== null
            ? (float) $dailyOpening->cash_opening
            : $this->previousClosingCash($agent, $date);

        // Cash: use deltas as per TransactionService for all types
        $cashIn = 0.0;
        $cashOut = 0.0;
        foreach ($transactions as $t) {
            $delta = $this->cashDelta($t->type, (float) $t->amount);
            if ($delta > 0) {
                $cashIn += $delta;
            } elseif ($delta < 0) {
                $cashOut += abs($delta);
            }
        }
        $cashDeposits = $cashIn;
        $cashWithdrawals = $cashOut;

        return [
            'date' => $date,
            'openingCash' => $openingCash,
            'cashDeposits' => $cashDeposits,
            'cashWithdrawals' => $cashWithdrawals,
            'expectedCash' => round($openingCash + $cashDeposits - $cashWithdrawals, 2),
            'openingFloat' => round(array_sum(array_column($rows, 'opening')), 2),
            'expectedFloat' => round(array_sum(array_column($rows, 'expected')), 2),
            'networks' => $rows,
        ];
    }

    private function cashDelta(string $type, float $amount): float
    {
        if (! in_array($type, ['deposit', 'withdrawal', 'float_deposit', 'float_topup', 'wallet_to_bank', 'airtime'], true)) {
            return 0;
        }

        $direction = in_array($type, ['deposit', 'airtime'], true) ? 1 : -1;

        return $direction * $amount;
    }

    private function floatDelta(string $type, float $amount): float
    {
        return match ($type) {
            'deposit' => -$amount,
            'withdrawal', 'bank_to_wallet', 'float_topup', 'float_deposit' => $amount,
            default => -$amount,
        };
    }

    /**
     * Rebuild the displayable run from a stored reconciliation record.
     *
     * @return array{
     *     openingCash: float,
     *     cashDeposits: float,
     *     cashWithdrawals: float,
     *     expectedCash: float,
     *     countedCash: float,
     *     cashVariance: float,
     *     openingFloat: float,
     *     expectedFloat: float,
     *     countedFloat: float,
     *     tieOut: float,
     *     networks: array<int, array{
     *         network: string,
     *         opening: float,
     *         deposits: float,
     *         withdrawals: float,
     *         expected: float,
     *         counted: float,
     *         variance: float,
     *     }>,
     * }
     */
    private function runFromRecord(Reconciliation $reconciliation): array
    {
        $rows = collect($reconciliation->network_balances ?? [])->map(function (array $row): array {
            $expected = (float) ($row['expected'] ?? $row['system'] ?? 0);
            $counted = (float) ($row['counted'] ?? 0);

            return [
                'network' => $row['network'] ?? 'Network',
                'opening' => (float) ($row['opening'] ?? 0),
                'deposits' => (float) ($row['deposits'] ?? 0),
                'withdrawals' => (float) ($row['withdrawals'] ?? 0),
                'expected' => $expected,
                'counted' => $counted,
                'variance' => round($counted - $expected, 2),
            ];
        })->values()->all();

        return [
            'openingCash' => (float) $reconciliation->opening_cash,
            'cashDeposits' => (float) $reconciliation->cash_deposits,
            'cashWithdrawals' => (float) $reconciliation->cash_withdrawals,
            'expectedCash' => (float) $reconciliation->expected_cash,
            'countedCash' => (float) $reconciliation->counted_cash,
            'cashVariance' => (float) $reconciliation->cash_variance,
            'openingFloat' => (float) $reconciliation->opening_float,
            'expectedFloat' => (float) $reconciliation->total_float,
            'countedFloat' => (float) collect($reconciliation->network_balances ?? [])->sum('counted'),
            'tieOut' => (float) $reconciliation->tie_out,
            'networks' => $rows,
        ];
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
