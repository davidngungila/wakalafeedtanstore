<?php

namespace App\Http\Controllers;

use App\Models\DailyOpening;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyOpeningController extends Controller
{
    public function create(): View|RedirectResponse
    {
        $agent = cash_point();

        if ($agent === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first.');
        }

        $todayOpening = DailyOpening::forAgentAndDate($agent->id, today())->first();

        if ($todayOpening) {
            return redirect()->route('daily-opening.show', $todayOpening);
        }

        $networks = Network::active()->orderBy('name')->get(['id', 'name', 'color']);
        $currentBalances = $agent->balances()->with('network')->get()->keyBy('network_id');

        return view('daily_opening.create', compact('agent', 'networks', 'currentBalances'));
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
        ]);

        $opening = DailyOpening::create([
            'agent_id' => $agent->id,
            'user_id' => auth()->id(),
            'opening_date' => today(),
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

    public function show(DailyOpening $dailyOpening): View
    {
        $agent = cash_point();

        if ($agent === null || $dailyOpening->agent_id !== $agent->id) {
            abort(403);
        }

        $openingDate = $dailyOpening->opening_date;
        $isToday = $openingDate->isSameDay(today());
        $isAdmin = is_admin();

        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);
        $currentBalances = $agent->balances()->with('network')->get()->keyBy('network_id');

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
        $todayFees = (float) $todayTransactions->sum('fee');

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

        $cashCurrent = $agent->cash_balance;
        $floatCurrent = $agent->totalFloat();

        $openingDate = $dailyOpening->opening_date;
        $isToday = $openingDate->isSameDay(today());
        $isAdmin = is_admin();

        return view('daily_opening.show', compact(
            'dailyOpening',
            'agent',
            'networks',
            'currentBalances',
            'todayVolume',
            'todayCommission',
            'todayCount',
            'todayDeposits',
            'todayWithdrawals',
            'todayFees',
            'expectedClosingCash',
            'cashCurrent',
            'floatCurrent',
            'todayTransactions',
            'todayFloatTransactions',
            'reconciliationForDay',
            'openingDate',
            'isToday',
            'isAdmin',
        ));
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

        \Log::info('Close day request data', [
            'all' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'method' => $request->method(),
        ]);

        $validated = $request->validate([
            'cash_closing' => ['required', 'numeric', 'min:0'],
            'float_closings' => ['required', 'array'],
            'float_closings.*' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $todayTransactions = Transaction::where('agent_id', $agent->id)
            ->whereDate('created_at', $dailyOpening->opening_date)
            ->where('status', 'completed')
            ->get();

        $dailyOpening->update([
            'is_closed' => true,
            'cash_closing' => $validated['cash_closing'],
            'float_closings' => $validated['float_closings'],
            'total_volume' => $todayTransactions->sum('amount'),
            'total_commission' => $todayTransactions->sum('commission'),
            'total_transactions' => $todayTransactions->count(),
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
            'float_total_closing' => array_sum($validated['float_closings']),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Day closed successfully.']);
        }

        return redirect()->route('daily-opening.show', $dailyOpening)->with('status', 'Day closed successfully.');
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
