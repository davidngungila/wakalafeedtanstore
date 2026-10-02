<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\DailyOpening;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Services\ExportService;
use App\Support\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DailyOpeningController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $agent = cash_point();

        if ($agent === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first.');
        }

        $current = Shift::current();

        $todayOpening = DailyOpening::forAgentAndDate($agent->id, Carbon::parse($current['date']))
            ->whereIn('shift', [$current['shift'], Shift::FULL])
            ->first();

        if ($todayOpening) {
            return redirect()->route('daily-opening.show', $todayOpening);
        }

        $awaitingApproval = $this->unapprovedReconciliationExists($agent->id);

        if ($awaitingApproval) {
            return redirect()->route('reconciliation.index')
                ->with('error', 'A reconciliation from a previous day is awaiting supervisor approval. It must be approved before a new shift can be opened.');
        }

        $currentShift = Shift::current();

        $networks = Network::active()->orderBy('name')->get(['id', 'name', 'color']);
        $currentBalances = $agent->balances()->with('network')->get()->keyBy('network_id');

        return view('daily_opening.create', compact('agent', 'networks', 'currentBalances', 'currentShift'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $agent = cash_point();

        if ($agent === null) {
            $message = 'Set up the cash point first.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('cash-point.index')->with('error', $message);
        }

        $validated = $request->validate([
            'cash_opening' => ['required', 'numeric', 'min:0'],
            'float_openings' => ['required', 'array'],
            'float_openings.*' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
            'shift' => ['nullable', 'in:full,morning,night'],
        ]);

        if ($this->unapprovedReconciliationExists($agent->id)) {
            $message = 'A reconciliation from a previous day is awaiting supervisor approval. It must be approved before a new shift can be opened.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('reconciliation.index')->with('error', $message);
        }

        $shiftInfo = Shift::current();
        $shift = $validated['shift'] ?? $shiftInfo['shift'];

        $opening = DailyOpening::create([
            'agent_id' => $agent->id,
            'user_id' => auth()->id(),
            'opening_date' => $shiftInfo['date'],
            'shift' => $shift,
            'cash_opening' => $validated['cash_opening'],
            'float_openings' => $validated['float_openings'],
            'notes' => $validated['notes'] ?? null,
        ]);

        // Initialize NetworkBalance opening_balance from this entry
        foreach ($validated['float_openings'] as $networkId => $amount) {
            $balance = $agent->balances()->where('network_id', $networkId)->first();
            if ($balance) {
                $balance->update(['opening_balance' => $amount]);
            }
        }

        // Set agent cash_balance to opening cash
        $agent->update(['cash_balance' => $validated['cash_opening']]);

        $this->recordAudit('Daily opening recorded', 'DailyOpening', $opening->id, [
            'cash' => $validated['cash_opening'],
            'float_total' => array_sum($validated['float_openings']),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Daily opening recorded successfully.']);
        }

        return redirect()->route('daily-opening.show', $opening)->with('status', 'Daily opening recorded successfully.');
    }

    private function unapprovedReconciliationExists(int $agentId): bool
    {
        $approvedDays = Reconciliation::where('agent_id', $agentId)
            ->whereNotNull('approved_at')
            ->pluck('reconciliation_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        $previousTxnDays = Transaction::where('agent_id', $agentId)
            ->where('status', 'completed')
            ->where('created_at', '<', now()->startOfDay())
            ->selectRaw('DATE(created_at) as day')
            ->distinct()
            ->pluck('day');

        foreach ($previousTxnDays as $day) {
            if (! in_array($day, $approvedDays, true)) {
                return true;
            }
        }

        return false;
    }

    public function show(DailyOpening $dailyOpening): View
    {
        $agent = cash_point();

        if ($agent === null || $dailyOpening->agent_id !== $agent->id) {
            abort(403);
        }

        $summary = $this->closingSummary($dailyOpening, $agent);

        $openingDate = $dailyOpening->opening_date;
        $isToday = $openingDate->isSameDay(today());
        $isAdmin = is_admin();

        return view('daily_opening.show', array_merge($summary, compact(
            'dailyOpening',
            'agent',
            'openingDate',
            'isToday',
            'isAdmin',
        )));
    }

    /**
     * Dedicated closing page: expected against counted cash and float for the day.
     */
    public function closeForm(DailyOpening $dailyOpening): View|RedirectResponse
    {
        $agent = cash_point();

        if ($agent === null || $dailyOpening->agent_id !== $agent->id) {
            abort(403);
        }

        if ($dailyOpening->is_closed) {
            return redirect()->route('daily-opening.show', $dailyOpening)
                ->with('status', 'This day is already closed.');
        }

        $summary = $this->closingSummary($dailyOpening, $agent);

        return view('daily_opening.close', array_merge($summary, [
            'dailyOpening' => $dailyOpening,
            'agent' => $agent,
            'isAdmin' => is_admin(),
            'hasUnapprovedReconciliation' => $this->unapprovedReconciliationExists($agent->id),
        ]));
    }

    /**
     * Every figure needed to describe the closing position of a day.
     *
     * @return array<string, mixed>
     */
    private function closingSummary(DailyOpening $dailyOpening, Agent $agent): array
    {
        $openingDate = $dailyOpening->opening_date;

        $todayTransactions = Transaction::where('agent_id', $agent->id)
            ->whereDate('created_at', $openingDate)
            ->where('status', 'completed')
            ->with(['network', 'operator'])
            ->latest()
            ->get();

        $todayFloatTransactions = FloatTransaction::where('agent_id', $agent->id)
            ->whereDate('created_at', $openingDate)
            ->with(['network', 'operator'])
            ->latest()
            ->get();

        $reconciliationForDay = Reconciliation::where('agent_id', $agent->id)
            ->where('reconciliation_date', $openingDate->toDateString())
            ->latest()
            ->first();

        $cashInTypes = ['deposit', 'airtime'];
        $cashOutTypes = ['withdrawal', 'wallet_to_bank', 'cash_to_float'];

        $todayDeposits = (float) $todayTransactions->whereIn('type', $cashInTypes)->sum('amount');
        $todayWithdrawals = (float) $todayTransactions->whereIn('type', $cashOutTypes)->sum('amount');
        $floatCashDelta = (float) $todayFloatTransactions->sum(fn (FloatTransaction $floatTransaction): float => $floatTransaction->cashDelta());
        if ($floatCashDelta > 0) {
            $todayDeposits += $floatCashDelta;
        } elseif ($floatCashDelta < 0) {
            $todayWithdrawals += abs($floatCashDelta);
        }

        $todayVolume = (float) $todayTransactions->sum('amount');
        $todayCommission = (float) $todayTransactions->sum('commission');
        $todayCount = $todayTransactions->count();

        $storedVolume = (float) $dailyOpening->total_volume;
        $storedCommission = (float) $dailyOpening->total_commission;
        $storedCount = (int) $dailyOpening->total_transactions;

        if ($storedCount > 0 || $storedVolume > 0) {
            $todayVolume = $storedVolume;
            $todayCommission = $storedCommission;
            $todayCount = $storedCount;
        }

        $dailyOpening->updateQuietly([
            'total_volume' => $todayVolume,
            'total_commission' => $todayCommission,
            'total_transactions' => $todayCount,
        ]);

        $expectedClosingCash = ((float) $dailyOpening->cash_opening) + $todayDeposits - $todayWithdrawals + $todayCommission;

        return [
            'networks' => Network::orderBy('name')->get(['id', 'name', 'color']),
            'currentBalances' => $agent->balances()->with('network')->get()->keyBy('network_id'),
            'todayTransactions' => $todayTransactions,
            'todayFloatTransactions' => $todayFloatTransactions,
            'reconciliationForDay' => $reconciliationForDay,
            'todayVolume' => $todayVolume,
            'todayCommission' => $todayCommission,
            'todayCount' => $todayCount,
            'todayDeposits' => $todayDeposits,
            'todayWithdrawals' => $todayWithdrawals,
            'todayFees' => (float) $todayTransactions->sum('fee'),
            'expectedClosingCash' => $expectedClosingCash,
            'cashCurrent' => $agent->cash_balance,
            'floatCurrent' => $agent->totalFloat(),
        ];
    }

    public function close(Request $request, DailyOpening $dailyOpening): JsonResponse|RedirectResponse
    {
        $agent = cash_point();

        if ($agent === null || $dailyOpening->agent_id !== $agent->id) {
            $message = 'Unauthorized.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 403);
            }

            return back()->with('error', $message);
        }

        if ($dailyOpening->is_closed) {
            $message = 'Daily opening already closed.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $validated = $request->validate([
            'cash_closing' => ['required', 'numeric', 'min:0'],
            'float_closings' => ['required', 'array'],
            'float_closings.*' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $networks = Network::orderBy('name')->get(['id', 'name']);
        $counted = collect($validated['float_closings'])->map(fn ($amount): float => (float) $amount);
        $missing = $networks->reject(fn (Network $network): bool => $counted->has($network->id));

        if ($missing->isNotEmpty()) {
            return $this->closeFailed(
                $request,
                'Count the float for every network before closing. Missing: '.$missing->pluck('name')->implode(', ').'.'
            );
        }

        $summary = $this->closingSummary($dailyOpening, $agent);

        $cashVariance = round((float) $validated['cash_closing'] - (float) $summary['expectedClosingCash'], 2);

        if (abs($cashVariance) >= 0.005 && blank($validated['notes'] ?? null)) {
            return $this->closeFailed($request, 'Add a note explaining the cash variance of '.money($cashVariance).' before closing the day.');
        }

        $dailyOpening->update([
            'is_closed' => true,
            'cash_closing' => $validated['cash_closing'],
            'float_closings' => $validated['float_closings'],
            'total_volume' => $summary['todayVolume'],
            'total_commission' => $summary['todayCommission'],
            'total_transactions' => $summary['todayCount'],
            'closed_at' => now(),
            'notes' => $validated['notes'] ?? $dailyOpening->notes,
        ]);

        // Update agent balances to closing values
        $agent->update(['cash_balance' => $validated['cash_closing']]);

        foreach ($validated['float_closings'] as $networkId => $amount) {
            $balance = $agent->balances()->where('network_id', $networkId)->first();
            if ($balance) {
                $balance->update(['balance' => $amount]);
            }
        }

        $this->recordAudit('Daily closing recorded', 'DailyOpening', $dailyOpening->id, [
            'cash_closing' => $validated['cash_closing'],
            'cash_variance' => $cashVariance,
            'float_total_closing' => array_sum($validated['float_closings']),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Day closed successfully.',
                'redirect' => route('daily-opening.show', $dailyOpening),
            ]);
        }

        return redirect()->route('daily-opening.show', $dailyOpening)->with('status', 'Day closed successfully.');
    }

    private function closeFailed(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => false, 'message' => $message], 422);
        }

        return back()->with('error', $message)->withInput();
    }

    public function index(): View
    {
        $agent = cash_point();

        if ($agent === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first.');
        }

        $openings = DailyOpening::where('agent_id', $agent->id)
            ->latest('opening_date')
            ->paginate(30);

        $exportColumns = $this->exportColumns();
        $exportRoute = route('daily-opening.export');

        return view('daily_opening.index', compact('openings', 'exportColumns', 'exportRoute'));
    }

    public function export(Request $request, ExportService $export)
    {
        $agent = cash_point();

        if ($agent === null) {
            return back()->with('error', 'No cash point configured.');
        }

        $available = $this->exportColumns();
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = DailyOpening::where('agent_id', $agent->id)
            ->latest('opening_date')
            ->limit(5000)
            ->get()
            ->map(fn (DailyOpening $d) => [
                'date' => $d->opening_date,
                'cash_opening' => money($d->cash_opening),
                'cash_closing' => $d->cash_closing !== null ? money($d->cash_closing) : '—',
                'total_volume' => money($d->total_volume),
                'total_commission' => money($d->total_commission),
                'total_transactions' => $d->total_transactions,
                'status' => $d->is_closed ? 'Closed' : 'Open',
            ])
            ->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'Daily Openings Report';
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
            ['key' => 'cash_opening', 'label' => 'Cash Opening'],
            ['key' => 'cash_closing', 'label' => 'Cash Closing'],
            ['key' => 'total_volume', 'label' => 'Total Volume'],
            ['key' => 'total_commission', 'label' => 'Commission'],
            ['key' => 'total_transactions', 'label' => 'Transactions'],
            ['key' => 'status', 'label' => 'Status'],
        ];
    }
}
