<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\DailyOpening;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Transaction;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FloatController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $cashPoint = cash_point();

        if ($cashPoint === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first before managing float.');
        }

        // Admin can pick any date; cashiers always see today
        $selectedDate = $request->input('date');
        $isAdmin = is_admin();

        if ($isAdmin && $selectedDate) {
            try {
                $selectedDate = Carbon::parse($selectedDate)->toDateString();
            } catch (\Throwable) {
                $selectedDate = today()->toDateString();
            }
        } else {
            $selectedDate = today()->toDateString();
        }

        $viewDate = Carbon::parse($selectedDate);

        $todayOpening = DailyOpening::forAgentAndDate($cashPoint->id, $viewDate)->first();

        // For non-admin, keep original guard (must have today opening and not closed)
        if (! $isAdmin) {
            $todayOpeningCheck = DailyOpening::forAgentAndDate($cashPoint->id, today())->first();
            if (! $todayOpeningCheck) {
                return redirect()->route('daily-opening.create')->with('error', 'Record daily opening first before managing float.');
            }
            if ($todayOpeningCheck->is_closed) {
                return redirect()->route('daily-opening.show', $todayOpeningCheck)->with('error', 'Daily session is already closed. Cannot manage float.');
            }
            // Non-admin sees today's opening only
            $todayOpening = $todayOpeningCheck;
            $selectedDate = today()->toDateString();
            $viewDate = today();
        }

        // For admin with selected date, compute per-network current as of that day's end (opening + day's net)
        if ($isAdmin && ! $viewDate->isSameDay(today())) {
            $networksAll = Network::orderBy('name')->get(['id', 'name', 'color']);
            $balancesForDate = collect();
            foreach ($networksAll as $net) {
                $bal = $cashPoint->balances()->where('network_id', $net->id)->first();
                $openingVal = $todayOpening ? $todayOpening->getFloatOpening($net->id) : (float) ($bal?->opening_balance ?? 0);
                if ($openingVal == 0.0 && $bal) {
                    $openingVal = (float) $bal->opening_balance;
                }
                // Float delta for Transactions on that date for this network — float_topup/float_deposit/bank_to_wallet are bank float IN
                $txsForNet = Transaction::where('agent_id', $cashPoint->id)->where('network_id', $net->id)->whereDate('created_at', $viewDate)->where('status', 'completed')->get();
                $netTxFloat = 0.0;
                foreach ($txsForNet as $t) {
                    $netTxFloat += match ($t->type) {
                        'deposit' => -(float) $t->amount,
                        'withdrawal', 'bank_to_wallet', 'float_topup', 'float_deposit' => (float) $t->amount,
                        default => -(float) $t->amount,
                    };
                }
                // Float delta for FloatTransactions on that date — float_topup/cash_in are float IN
                $ftsForNet = FloatTransaction::where('agent_id', $cashPoint->id)->where('network_id', $net->id)->whereDate('created_at', $viewDate)->get();
                $netFloatTx = 0.0;
                foreach ($ftsForNet as $ft) {
                    $netFloatTx += match ($ft->type) {
                        'cash_in', 'float_topup' => (float) $ft->amount,
                        'cash_out', 'float_pull' => -(float) $ft->amount,
                        default => 0.0,
                    };
                }
                $currentForDate = $openingVal + $netTxFloat + $netFloatTx;
                $balancesForDate->push((object) [
                    'network' => $net,
                    'network_id' => $net->id,
                    'opening_balance' => $openingVal,
                    'balance' => $currentForDate,
                ]);
            }
            // Map to expected structure for view (has network relation)
            $balances = $balancesForDate->map(function ($b) {
                $b->network = $b->network;

                return $b;
            });
            // Summary for that date
            $summary = [
                'totalFloat' => (float) $balances->sum('balance'),
                'floatOut' => (float) $balances->sum('balance'),
                'totalCash' => (float) ($todayOpening ? $todayOpening->cash_opening : $cashPoint->cash_balance),
                'networks' => Network::count(),
            ];
            $summary['floatCapacity'] = $summary['totalFloat'] + $summary['totalCash'];
            // Override cash summary for that date: opening + net cash for that date
            $dayTxCash = Transaction::where('agent_id', $cashPoint->id)->whereDate('created_at', $viewDate)->where('status', 'completed')->get();
            $cashIn = 0.0;
            $cashOut = 0.0;
            foreach ($dayTxCash as $t) {
                $d = $this->cashDelta($t->type, (float) $t->amount);
                if ($d > 0) {
                    $cashIn += $d;
                } elseif ($d < 0) {
                    $cashOut += abs($d);
                }
            }
            $summary['totalCash'] = (float) ($todayOpening ? $todayOpening->cash_opening : 0) + $cashIn - $cashOut;
        } else {
            $balances = $cashPoint->balances()->with('network')->orderBy('network_id')->get();

            $summary = [
                'totalFloat' => (float) $balances->sum('balance'),
                'floatOut' => (float) $balances->sum('balance') + (float) $balances->sum('opening_balance'),
                'totalCash' => (float) $cashPoint->cash_balance,
                'networks' => Network::count(),
            ];
            $summary['floatCapacity'] = $summary['floatOut'] + $summary['totalCash'];
        }

        $floatQuery = FloatTransaction::with(['network', 'operator'])
            ->where('agent_id', $cashPoint->id);
        // Date filter for float transactions (admin and all) — supports single date or range
        if ($request->filled('from')) {
            try {
                $from = Carbon::parse($request->input('from'))->startOfDay();
                $floatQuery->where('created_at', '>=', $from);
            } catch (\Throwable) {
            }
        } elseif ($isAdmin) {
            $floatQuery->whereDate('created_at', $viewDate);
        }
        if ($request->filled('to')) {
            try {
                $to = Carbon::parse($request->input('to'))->endOfDay();
                $floatQuery->where('created_at', '<=', $to);
            } catch (\Throwable) {
            }
        } elseif ($request->filled('date_filter')) {
            try {
                $df = Carbon::parse($request->input('date_filter'))->toDateString();
                $floatQuery->whereDate('created_at', $df);
            } catch (\Throwable) {
            }
        }
        $floatTransactions = $floatQuery->latest()->limit(50)->get();

        $networks = Network::active()->orderBy('name')->get(['id', 'name', 'color']);
        $allNetworks = Network::orderBy('name')->get(['id', 'name', 'color']);

        $exportColumns = $this->exportColumns();
        $exportRoute = route('float.export');

        // For admin: also load openings for date picker and the selected opening's details
        $openings = collect();
        if ($isAdmin) {
            $openings = DailyOpening::where('agent_id', $cashPoint->id)->orderByDesc('opening_date')->limit(30)->get();
        }

        return view('float.index', compact('balances', 'floatTransactions', 'networks', 'allNetworks', 'summary', 'todayOpening', 'exportColumns', 'exportRoute', 'selectedDate', 'viewDate', 'openings', 'isAdmin'));
    }

    public function export(Request $request, ExportService $export)
    {
        $cashPoint = cash_point();

        if ($cashPoint === null) {
            return back()->with('error', 'No cash point configured.');
        }

        $available = $this->exportColumns();
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = FloatTransaction::with(['network', 'operator'])
            ->where('agent_id', $cashPoint->id)
            ->latest()
            ->limit(5000)
            ->get()
            ->map(fn (FloatTransaction $f) => [
                'reference' => $f->reference,
                'date' => $f->created_at->format('d M Y H:i'),
                'network' => $f->network?->name ?? '—',
                'type' => ucfirst(str_replace('_', ' ', $f->type)),
                'amount' => money($f->amount),
                'fee' => money($f->fee),
                'operator' => $f->operator?->name ?? '—',
                'status' => ucfirst($f->status),
                'notes' => $f->notes ?? '—',
            ])
            ->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'Float Transactions';
        $subtitle = 'Generated '.now()->format('d M Y H:i').' — '.$rows->count().' records';

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
            ['key' => 'network', 'label' => 'Network'],
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'amount', 'label' => 'Amount'],
            ['key' => 'fee', 'label' => 'Fee'],
            ['key' => 'operator', 'label' => 'Operator'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'notes', 'label' => 'Notes'],
        ];
    }

    public function create(Request $request): View|RedirectResponse
    {
        $cashPoint = cash_point();

        if ($cashPoint === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first before managing float.');
        }

        $isAdmin = is_admin();
        $selectedDate = $isAdmin ? ($request->input('date', today()->toDateString())) : today()->toDateString();
        try {
            $viewDate = Carbon::parse($selectedDate);
        } catch (\Throwable) {
            $viewDate = today();
            $selectedDate = $viewDate->toDateString();
        }

        $todayOpening = DailyOpening::forAgentAndDate($cashPoint->id, $viewDate)->first();

        if (! $isAdmin) {
            if (! $todayOpening) {
                return redirect()->route('daily-opening.create')->with('error', 'Record daily opening first before managing float.');
            }

            if ($todayOpening->is_closed) {
                return redirect()->route('daily-opening.show', $todayOpening)->with('error', 'Daily session is already closed. Cannot manage float.');
            }
        }

        $networks = Network::active()->pluck('name', 'id');
        $allNetworks = Network::orderBy('name')->get(['id', 'name', 'color']);
        $currentBalances = $cashPoint->balances()->with('network')->get()->keyBy('network_id');

        $dayTransactions = collect();
        $dayFloatTransactions = collect();
        if ($isAdmin) {
            // Load ALL transactions for the selected day (any status) so Reference dropdown is complete
            $dayTransactions = Transaction::with(['network'])
                ->where('agent_id', $cashPoint->id)
                ->whereDate('created_at', $viewDate)
                ->latest()
                ->limit(100)
                ->get();
            $dayFloatTransactions = FloatTransaction::with(['network'])
                ->where('agent_id', $cashPoint->id)
                ->whereDate('created_at', $viewDate)
                ->latest()
                ->limit(100)
                ->get();
        }

        return view('float.create', compact('networks', 'allNetworks', 'currentBalances', 'selectedDate', 'viewDate', 'isAdmin', 'todayOpening', 'dayTransactions', 'dayFloatTransactions'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'network_id' => ['required', 'exists:networks,id'],
            'type' => ['required', 'in:cash_in,cash_out,float_topup,float_pull'],
            'amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
            'float_date' => ['nullable', 'date'],
            'transaction_id' => ['nullable', 'exists:transactions,id'],
        ]);

        $agent = cash_point();

        if ($agent === null) {
            $message = 'Set up the cash point first before managing float.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('cash-point.index')->with('error', $message);
        }

        $isAdmin = is_admin();
        $targetDate = today();
        if ($isAdmin && $request->filled('float_date')) {
            try {
                $targetDate = Carbon::parse($request->input('float_date'));
            } catch (\Throwable) {
                $targetDate = today();
            }
        } elseif ($isAdmin && $request->filled('date')) {
            try {
                $targetDate = Carbon::parse($request->input('date'));
            } catch (\Throwable) {
                $targetDate = today();
            }
        }

        $todayOpening = null;
        if (is_cashier()) {
            $todayOpening = DailyOpening::forAgentAndDate($agent->id, today())->first();

            if (! $todayOpening) {
                $message = 'Record daily opening first before managing float.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return redirect()->route('daily-opening.create')->with('error', $message);
            }

            if ($todayOpening->is_closed) {
                $message = 'Daily session is already closed. Cannot manage float.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return back()->with('error', $message);
            }
        } else {
            // For supervisor/admin, get opening for target date if exists (do not block if closed/missing when admin)
            $todayOpening = DailyOpening::forAgentAndDate($agent->id, $targetDate)->first();
        }

        $balance = NetworkBalance::firstOrCreate(
            ['agent_id' => $agent->id, 'network_id' => $validated['network_id']],
            ['opening_balance' => 0, 'balance' => 0]
        );

        $reference = 'FLT-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($agent, $balance, $validated, $reference, $targetDate, $todayOpening) {
            $amount = (float) $validated['amount'];

            match ($validated['type']) {
                'cash_in' => $balance->balance += $amount,
                'cash_out' => $balance->balance -= $amount,
                'float_topup' => $balance->balance += $amount,
                'float_pull' => $balance->balance -= $amount,
            };

            if (in_array($validated['type'], ['cash_out', 'float_pull'], true)) {
                $agent->cash_balance = (float) $agent->cash_balance - $amount;
            } elseif ($validated['type'] === 'cash_in') {
                $agent->cash_balance = (float) $agent->cash_balance + $amount;
            }

            $balance->save();
            $agent->save();

            // For admin with past date, set created_at to that date for correct reporting
            $now = $targetDate->isSameDay(today()) ? now() : $targetDate->copy()->setTime(now()->hour, now()->minute, now()->second);

            $ft = FloatTransaction::create([
                'reference' => $reference,
                'agent_id' => $agent->id,
                'network_id' => $balance->network_id,
                'type' => $validated['type'],
                'amount' => $amount,
                'fee' => $validated['type'] === 'float_topup' ? 1500 : 0,
                'status' => 'completed',
                'performed_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
                'daily_opening_id' => $todayOpening?->id,
            ]);

            if (! $targetDate->isSameDay(today())) {
                $ft->timestamps = false;
                $ft->created_at = $now;
                $ft->updated_at = $now;
                $ft->save();
            }

            // If admin selected a transaction to reference, link and update that transaction's notes for full system traceability
            if (! empty($validated['transaction_id'])) {
                $linkedTxn = Transaction::find($validated['transaction_id']);
                if ($linkedTxn) {
                    $refNote = 'Float '.$validated['type'].' '.money($amount).' ('.$reference.') on '.$targetDate->toDateString();
                    if (! empty($validated['notes'])) {
                        $refNote .= ' — '.$validated['notes'];
                    }
                    $linkedTxn->notes = trim(($linkedTxn->notes ? $linkedTxn->notes.' | ' : '').$refNote);
                    $linkedTxn->save();

                    // Also ensure the float transaction notes reference the transaction
                    $ft->notes = trim(($ft->notes ? $ft->notes.' | ' : '').'Linked txn '.$linkedTxn->reference.' ('.$linkedTxn->type.' '.money($linkedTxn->amount).')');
                    $ft->save();
                }
            }
        });

        $this->recordAudit('Float transaction processed', 'FloatTransaction', null, [
            'reference' => $reference,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'date' => $targetDate->toDateString(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Float transaction completed successfully.']);
        }

        return back()->with('status', 'Float transaction for '.$targetDate->toDateString().' completed successfully.');
    }

    /**
     * Admin: show edit opening for selected day
     */
    public function editOpening(Request $request): View|RedirectResponse
    {
        $agent = cash_point();
        if ($agent === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first.');
        }

        $dateStr = $request->input('date', today()->toDateString());
        try {
            $viewDate = Carbon::parse($dateStr);
        } catch (\Throwable) {
            $viewDate = today();
            $dateStr = $viewDate->toDateString();
        }

        $opening = DailyOpening::forAgentAndDate($agent->id, $viewDate)->first();
        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);
        $currentBalances = $agent->balances()->with('network')->get()->keyBy('network_id');

        return view('float.edit-opening', compact('agent', 'networks', 'currentBalances', 'opening', 'viewDate', 'dateStr'));
    }

    /**
     * Admin: update or create opening balances for selected day
     */
    public function updateOpening(Request $request): JsonResponse|RedirectResponse
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
            'opening_date' => ['required', 'date'],
            'cash_opening' => ['required', 'numeric', 'min:0'],
            'float_openings' => ['required', 'array'],
            'float_openings.*' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $viewDate = Carbon::parse($validated['opening_date']);

        $opening = DailyOpening::forAgentAndDate($agent->id, $viewDate)->first();

        DB::transaction(function () use ($agent, $validated, $viewDate, &$opening) {
            $floatOpenings = $validated['float_openings'];

            if ($opening) {
                $opening->update([
                    'cash_opening' => $validated['cash_opening'],
                    'float_openings' => $floatOpenings,
                    'notes' => $validated['notes'] ?? $opening->notes,
                ]);
            } else {
                $opening = DailyOpening::create([
                    'agent_id' => $agent->id,
                    'user_id' => auth()->id(),
                    'opening_date' => $viewDate->toDateString(),
                    'cash_opening' => $validated['cash_opening'],
                    'float_openings' => $floatOpenings,
                    'notes' => $validated['notes'] ?? null,
                    'is_closed' => false,
                    'total_volume' => 0,
                    'total_commission' => 0,
                    'total_transactions' => 0,
                ]);
            }

            // Sync NetworkBalance opening_balance and current balance to match opening
            foreach ($floatOpenings as $networkId => $amount) {
                $balance = NetworkBalance::firstOrCreate(
                    ['agent_id' => $agent->id, 'network_id' => $networkId],
                    ['opening_balance' => 0, 'balance' => 0]
                );
                $balance->opening_balance = (float) $amount;
                // If this is today or future, also set current balance to opening if balance is 0 or if admin explicitly wants
                // For past dates, we keep current balance as is but update opening_balance for historical accuracy
                if ($viewDate->isSameDay(today()) || $viewDate->isFuture()) {
                    // For today, set balance to opening + net of that day's transactions (let transactions drive later)
                    // Keep simple: set balance to opening if no transactions yet that day
                    $dayTxCount = Transaction::where('agent_id', $agent->id)->whereDate('created_at', $viewDate)->count() +
                        FloatTransaction::where('agent_id', $agent->id)->whereDate('created_at', $viewDate)->count();
                    if ($dayTxCount === 0) {
                        $balance->balance = (float) $amount;
                    }
                }
                $balance->save();
            }

            // Update agent cash_balance if editing today
            if ($viewDate->isSameDay(today())) {
                $agent->cash_balance = (float) $validated['cash_opening'];
                $agent->save();
            }
        });

        $this->recordAudit('Opening balances updated for '.$viewDate->toDateString(), 'DailyOpening', $opening->id, [
            'cash_opening' => $validated['cash_opening'],
            'float_total' => array_sum($validated['float_openings']),
            'date' => $viewDate->toDateString(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Opening balances for '.$viewDate->format('d M Y').' saved.']);
        }

        return redirect()->route('float.index', ['date' => $viewDate->toDateString()])->with('status', 'Opening balances for '.$viewDate->format('d M Y').' saved.');
    }

    /**
     * Admin: directly update current float balances (NetworkBalance)
     */
    public function updateBalances(Request $request): JsonResponse|RedirectResponse
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
            'balances' => ['required', 'array'],
            'balances.*.network_id' => ['required', 'exists:networks,id'],
            'balances.*.opening_balance' => ['nullable', 'numeric', 'min:0'],
            'balances.*.balance' => ['required', 'numeric'],
            'cash_balance' => ['nullable', 'numeric'],
        ]);

        DB::transaction(function () use ($agent, $validated) {
            foreach ($validated['balances'] as $row) {
                $balance = NetworkBalance::firstOrCreate(
                    ['agent_id' => $agent->id, 'network_id' => $row['network_id']],
                    ['opening_balance' => 0, 'balance' => 0]
                );
                if (array_key_exists('opening_balance', $row) && $row['opening_balance'] !== null) {
                    $balance->opening_balance = (float) $row['opening_balance'];
                }
                $balance->balance = (float) $row['balance'];
                $balance->save();
            }

            if (array_key_exists('cash_balance', $validated) && $validated['cash_balance'] !== null) {
                $agent->cash_balance = (float) $validated['cash_balance'];
                $agent->save();
            }
        });

        $this->recordAudit('Float balances directly updated', 'NetworkBalance', null, $validated);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Float balances updated.']);
        }

        return back()->with('status', 'Float balances updated.');
    }

    public function destroy(Request $request, FloatTransaction $floatTransaction): JsonResponse|RedirectResponse
    {
        $agent = cash_point();
        if ($agent === null || (int) $floatTransaction->agent_id !== (int) $agent->id) {
            abort(403);
        }

        $amount = (float) $floatTransaction->amount;
        $type = $floatTransaction->type;
        $networkId = (int) $floatTransaction->network_id;
        $agentId = (int) $floatTransaction->agent_id;
        $reference = $floatTransaction->reference;

        DB::transaction(function () use ($floatTransaction, $amount, $type, $networkId, $agentId) {
            $balance = NetworkBalance::where('agent_id', $agentId)->where('network_id', $networkId)->first();
            if ($balance) {
                match ($type) {
                    'cash_in' => $balance->balance -= $amount,
                    'cash_out' => $balance->balance += $amount,
                    'float_topup' => $balance->balance -= $amount,
                    'float_pull' => $balance->balance += $amount,
                    default => null,
                };
                $balance->save();
            }

            $agent = Agent::find($agentId);
            if ($agent) {
                if (in_array($type, ['cash_out', 'float_pull'], true)) {
                    $agent->cash_balance = (float) $agent->cash_balance + $amount;
                } elseif ($type === 'cash_in') {
                    $agent->cash_balance = (float) $agent->cash_balance - $amount;
                }
                $agent->save();
            }

            $floatTransaction->delete();
        });

        $this->recordAudit('Float transaction deleted', 'FloatTransaction', $floatTransaction->id, ['reference' => $reference, 'type' => $type, 'amount' => $amount]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Float transaction '.$reference.' deleted and balances reverted.']);
        }

        return back()->with('status', 'Float transaction '.$reference.' deleted.');
    }

    private function cashDelta(string $type, float $amount): float
    {
        if (! in_array($type, ['deposit', 'withdrawal', 'wallet_to_bank', 'airtime'], true)) {
            return 0;
        }

        $direction = in_array($type, ['deposit', 'airtime'], true) ? 1 : -1;

        return $direction * $amount;
    }
}
