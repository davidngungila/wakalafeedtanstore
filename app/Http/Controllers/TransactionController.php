<?php

namespace App\Http\Controllers;

use App\Models\DailyOpening;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(private readonly TransactionService $transactions) {}

    public function index(Request $request): View
    {
        $query = Transaction::with(['network', 'agent', 'operator', 'dailyOpening']);

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

        if ($agent === null) {
            $message = 'Set up the cash point first (Settings → Cash Point), then try again.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('cash-point.index')->with('error', $message);
        }

        // Check if daily opening is recorded for today (only for cashiers)
        if (is_cashier()) {
            $todayOpening = DailyOpening::forAgentAndDate($agent->id, today())->first();

            if (! $todayOpening) {
                $message = 'Record daily opening first before processing transactions.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return redirect()->route('daily-opening.create')->with('error', $message);
            }

            if ($todayOpening->is_closed) {
                $message = 'Daily session is already closed. Cannot process transactions.';
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return back()->with('error', $message);
            }
        }

        $transaction = $this->transactions->process(
            [
                'network_id' => $validated['network_id'],
                'type' => $validated['type'],
                'customer_name' => $validated['customer_name'] ?? null,
                'customer_phone' => $validated['customer_phone'],
                'amount' => $validated['amount'],
            ],
            $agent,
            auth()->id(),
        );

        // Associate with today's daily opening (if exists, e.g. for cashiers)
        if ($todayOpening) {
            $transaction->update(['daily_opening_id' => $todayOpening->id]);
        }

        $this->recordAudit('Transaction processed', 'Transaction', $transaction->id, [
            'reference' => $transaction->reference,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction processed successfully.']);
        }

        return back()->with('status', 'Transaction processed successfully.');
    }

    public function receipt(Request $request, Transaction $transaction): View|RedirectResponse
    {
        $raw = $request->route('transaction');
        // Only redirect if raw is plain numeric id (e.g. /transactions/47/receipt) -> encrypted
        // Do NOT compare encrypted strings directly: encryption uses random IV so each encrypt is different
        if (is_numeric($raw) && (string) $raw === (string) $transaction->id) {
            return redirect()->route('transactions.receipt', $transaction);
        }

        $transaction->load(['network', 'agent', 'operator', 'reverser', 'dailyOpening', 'smsMessages.device']);

        return view('transactions.receipt', compact('transaction'));
    }

    public function receiptPdf(Request $request, Transaction $transaction)
    {
        $raw = $request->route('transaction');
        if (is_numeric($raw) && (string) $raw === (string) $transaction->id) {
            return redirect()->route('transactions.receipt.pdf', $transaction);
        }

        $transaction->load(['network', 'agent', 'operator', 'reverser', 'dailyOpening', 'smsMessages.device']);

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('transactions.receipt-pdf', compact('transaction'));
        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

        return $pdf->download('receipt-'.$transaction->reference.'.pdf');
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

            if ($transaction->daily_opening_id) {
                $opening = DailyOpening::find($transaction->daily_opening_id);
                if ($opening) {
                    $opening->reverseTransactionVolume($amount, (float) $transaction->commission);
                }
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
}
