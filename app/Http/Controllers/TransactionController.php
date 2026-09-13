<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\CommissionRate;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Transaction::with(['network', 'agent', 'operator']);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('network') && $request->input('network') !== 'all') {
            $query->where('network_id', $request->input('network'));
        }

        if ($request->filled('q')) {
            $needle = $request->input('q');
            $query->where(function ($sub) use ($needle) {
                $sub->where('reference', 'like', "%{$needle}%")
                    ->orWhere('customer_name', 'like', "%{$needle}%")
                    ->orWhere('customer_phone', 'like', "%{$needle}%")
                    ->orWhere('provider_reference', 'like', "%{$needle}%");
            });
        }

        $transactions = $query->latest()->limit(200)->get();

        $todayTotals = [
            'deposits' => (float) Transaction::whereDate('created_at', today())->where('type', 'deposit')->where('status', 'completed')->sum('amount'),
            'withdrawals' => (float) Transaction::whereDate('created_at', today())->where('type', 'withdrawal')->where('status', 'completed')->sum('amount'),
            'volume' => (float) Transaction::whereDate('created_at', today())->where('status', 'completed')->sum('amount'),
            'count' => (int) Transaction::whereDate('created_at', today())->count(),
            'failed' => (int) Transaction::whereDate('created_at', today())->whereIn('status', ['failed', 'reversed'])->count(),
        ];

        $combos = $this->combos();

        return view('transactions.index', compact('transactions', 'todayTotals', 'combos') + ['filters' => $request->only(['status', 'type', 'network', 'q'])]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'network_id' => ['required', 'exists:networks,id'],
            'type' => ['required', 'in:deposit,withdrawal,send_money,bill_payment,airtime,data,bank_to_wallet,wallet_to_bank'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'amount' => ['required', 'numeric', 'min:1'],
        ]);

        $agent = cash_point();
        $rate = $this->commissionFor($agent, $validated['network_id'], $validated['type'], $validated['amount']);

        $commission = round((float) $validated['amount'] * $rate / 100, 2);
        $fee = $this->feeFor($validated['type'], $validated['amount']);

        $balance = NetworkBalance::firstOrCreate(
            ['agent_id' => $agent->id, 'network_id' => $validated['network_id']],
            ['opening_balance' => 0, 'balance' => 0]
        );

        $reference = 'TXN-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($agent, $balance, $validated, $commission, $fee, $reference) {
            $adjustFloat = function (float $delta) use ($balance) {
                $balance->balance += $delta;
                $balance->save();
            };

            match ($validated['type']) {
                'deposit' => $adjustFloat((float) $validated['amount']),
                default => $adjustFloat(-(float) $validated['amount']),
            };

            if (in_array($validated['type'], ['deposit', 'withdrawal'], true)) {
                $direction = $validated['type'] === 'deposit' ? -1 : 1;
                $agent->cash_balance = ((float) $agent->cash_balance) + $direction * (float) $validated['amount'];
                $agent->save();
            }

            Transaction::create([
                'reference' => $reference,
                'agent_id' => $agent->id,
                'network_id' => $validated['network_id'],
                'type' => $validated['type'],
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'],
                'amount' => $validated['amount'],
                'fee' => $fee,
                'commission' => $commission,
                'status' => 'completed',
                'provider_reference' => 'SR'.random_int(10000000, 99999999),
                'performed_by' => auth()->id(),
                'notes' => 'Processed on counter',
            ]);
        });

        $this->recordAudit('Transaction processed', 'Transaction', null, [
            'reference' => $reference,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction processed successfully.']);
        }

        return back()->with('status', 'Transaction processed successfully.');
    }

    public function reverse(Request $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        if ($transaction->status === 'reversed') {
            return response()->json(['success' => false, 'message' => 'Transaction is already reversed.'], 422);
        }

        DB::transaction(function () use ($transaction, $validated) {
            $amount = (float) $transaction->amount;
            $balance = NetworkBalance::where('agent_id', $transaction->agent_id)
                ->where('network_id', $transaction->network_id)
                ->first();

            if ($balance) {
                $delta = in_array($transaction->type, ['deposit', 'bank_to_wallet'], true) ? -$amount : $amount;
                $balance->balance += $delta;
                $balance->save();
            }

            if (in_array($transaction->type, ['deposit', 'withdrawal'], true)) {
                $direction = $transaction->type === 'deposit' ? 1 : -1;
                $agent = $transaction->agent;
                $agent->cash_balance = ((float) $agent->cash_balance) + $direction * $amount;
                $agent->save();
            }

            $transaction->update([
                'status' => 'reversed',
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'reversal_reason' => $validated['reason'],
            ]);
        });

        $this->recordAudit('Transaction reversed', 'Transaction', $transaction->id, [
            'reference' => $transaction->reference,
            'reason' => $validated['reason'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction reversed successfully.']);
        }

        return back()->with('status', 'Transaction reversed successfully.');
    }

    /**
     * @return array{networks: array, types: array}
     */
    private function combos(): array
    {
        return [
            'networks' => Network::orderBy('name')->get(['id', 'name', 'color']),
            'types' => ['deposit', 'withdrawal', 'send_money', 'bill_payment', 'airtime', 'data', 'bank_to_wallet', 'wallet_to_bank'],
        ];
    }

    private function commissionFor(Agent $agent, int $networkId, string $type, float $amount): float
    {
        $rate = CommissionRate::where('network_id', $networkId)
            ->where('agent_level', $agent->agent_level)
            ->where('transaction_type', $type)
            ->where('is_active', true)
            ->first();

        if (! $rate) {
            return 0;
        }

        if ($rate->min_amount > 0 && $amount < $rate->min_amount) {
            return $rate->rate;
        }

        if ($rate->max_amount > 0 && $amount > $rate->max_amount) {
            return $rate->rate;
        }

        return $rate->rate;
    }

    private function feeFor(string $type, float $amount): float
    {
        return match ($type) {
            'withdrawal' => min(5000, max(200, round($amount * 0.002, 2))),
            'bill_payment' => round($amount * 0.003, 2),
            'bank_to_wallet', 'wallet_to_bank' => round($amount * 0.001, 2),
            default => 0,
        };
    }
}
