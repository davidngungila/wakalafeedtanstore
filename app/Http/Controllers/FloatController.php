<?php

namespace App\Http\Controllers;

use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\NetworkBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FloatController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $cashPoint = cash_point();

        if ($cashPoint === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first before managing float.');
        }

        $balances = $cashPoint->balances()->with('network')->orderBy('network_id')->get();

        $summary = [
            'totalFloat' => (float) $balances->sum('balance'),
            'floatOut' => (float) $balances->sum('balance') + (float) $balances->sum('opening_balance'),
            'totalCash' => (float) $cashPoint->cash_balance,
            'networks' => Network::count(),
        ];
        $summary['floatCapacity'] = $summary['floatOut'] + $summary['totalCash'];

        $floatTransactions = FloatTransaction::with(['network', 'operator'])
            ->latest()
            ->limit(50)
            ->get();

        $networks = Network::active()->pluck('name', 'id');

        return view('float.index', compact('balances', 'floatTransactions', 'networks', 'summary'));
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'network_id' => ['required', 'exists:networks,id'],
            'type' => ['required', 'in:cash_in,cash_out,float_topup,float_pull'],
            'amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $agent = cash_point();

        if ($agent === null) {
            $message = 'Set up the cash point first before managing float.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('cash-point.index')->with('error', $message);
        }

        $balance = NetworkBalance::firstOrCreate(
            ['agent_id' => $agent->id, 'network_id' => $validated['network_id']],
            ['opening_balance' => 0, 'balance' => 0]
        );

        $reference = 'FLT-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($agent, $balance, $validated, $reference) {
            $amount = (float) $validated['amount'];

            match ($validated['type']) {
                'cash_in' => $balance->balance += $amount,
                'cash_out' => $balance->balance -= $amount,
                'float_topup' => $balance->balance += $amount,
                'float_pull' => $balance->balance -= $amount,
            };

            if (in_array($validated['type'], ['cash_out', 'float_pull'], true)) {
                $agent->cash_balance -= $amount;
            } elseif ($validated['type'] === 'cash_in') {
                $agent->cash_balance += $amount;
            }

            $balance->save();
            $agent->save();

            FloatTransaction::create([
                'reference' => $reference,
                'agent_id' => $agent->id,
                'network_id' => $balance->network_id,
                'type' => $validated['type'],
                'amount' => $amount,
                'fee' => $validated['type'] === 'float_topup' ? 1500 : 0,
                'status' => 'completed',
                'performed_by' => auth()->id(),
                'notes' => $validated['notes'] ?? null,
            ]);
        });

        $this->recordAudit('Float transaction processed', 'FloatTransaction', null, [
            'reference' => $reference,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Float transaction completed successfully.']);
        }

        return back()->with('status', 'Float transaction completed successfully.');
    }
}
