<?php

namespace App\Http\Controllers;

use App\Models\DailyOpening;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Services\ExportService;
use App\Services\TransactionJournalService;
use App\Services\TransactionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService $transactions,
        private readonly TransactionJournalService $journals = new TransactionJournalService,
    ) {}

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
        $exportColumns = $this->exportColumns();
        $exportRoute = route('transactions.export');

        return view('transactions.index', compact('transactions', 'todayTotals', 'combos', 'exportColumns', 'exportRoute') + ['filters' => $request->only(['status', 'type', 'network', 'q'])]);
    }

    public function export(Request $request, ExportService $export)
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

        $available = $this->exportColumns();
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = $request->input('format', 'pdf');
        $format = in_array($format, ['pdf', 'excel'], true) ? $format : 'pdf';

        $rows = $query->latest()->limit(5000)->get()->map(function (Transaction $t) {
            return [
                'reference' => $t->reference,
                'provider_reference' => $t->provider_reference ?? '—',
                'date' => $t->created_at->format('d M Y H:i'),
                'customer_name' => $t->customer_name ?? '—',
                'customer_phone' => $t->customer_phone ?? '—',
                'type' => txn_type_label($t->type),
                'type_raw' => $t->type,
                'network' => $t->network?->name ?? '—',
                'amount' => money($t->amount),
                'amount_raw' => (float) $t->amount,
                'fee' => money($t->fee),
                'commission' => money($t->commission),
                'status' => ucfirst($t->status),
                'agent' => $t->agent?->name ?? '—',
                'operator' => $t->operator?->name ?? '—',
                'running_cash' => $t->running_cash_balance !== null ? money($t->running_cash_balance) : '—',
                'running_float' => $t->running_float_balance !== null ? money($t->running_float_balance) : '—',
                'is_unusual' => $t->is_unusual ? 'Yes' : 'No',
                'unusual_reason' => $t->unusual_reason ?? '—',
            ];
        });

        // Map to export rows with only selected columns
        $exportRows = $rows->map(function (array $row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[$col['key']] = $row[$col['key']] ?? '';
            }

            return $out;
        });

        $title = 'Transactions Report';
        $subtitle = 'Filtered transactions — '.now()->format('d M Y H:i').' — '.count($exportRows).' records';
        $meta = [
            'filters' => array_filter($request->only(['status', 'type', 'network', 'q'])),
            'totals' => [
                'amount' => money($rows->sum('amount_raw')),
            ],
        ];

        if ($format === 'excel') {
            return $export->excel($title, $columns, $exportRows);
        }

        return $export->pdf($title, $subtitle, $columns, $exportRows, $meta);
    }

    private function exportColumns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'provider_reference', 'label' => 'Provider Ref'],
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'customer_name', 'label' => 'Customer'],
            ['key' => 'customer_phone', 'label' => 'Phone'],
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'network', 'label' => 'Network'],
            ['key' => 'amount', 'label' => 'Amount'],
            ['key' => 'fee', 'label' => 'Fee'],
            ['key' => 'commission', 'label' => 'Commission'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'agent', 'label' => 'Agent'],
            ['key' => 'operator', 'label' => 'Operator'],
            ['key' => 'running_cash', 'label' => 'Running Cash'],
            ['key' => 'running_float', 'label' => 'Running Float'],
            ['key' => 'is_unusual', 'label' => 'Unusual'],
            ['key' => 'unusual_reason', 'label' => 'Unusual Reason'],
        ];
    }

    public function create(Request $request): View
    {
        $combos = $this->combos();
        $smsMessages = SmsMessage::with(['device', 'network'])
            ->whereNull('transaction_id')
            ->whereIn('processing_status', ['NEEDS_REVIEW', 'FAILED', 'PARSED'])
            ->latest('server_received_at')
            ->limit(50)
            ->get();

        return view('transactions.create', compact('combos', 'smsMessages') + ['selectedSms' => $request->input('sms')]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'network_id' => ['required', 'exists:networks,id'],
            'type' => ['required', 'in:deposit,withdrawal,send_money,bill_payment,airtime,data,bank_to_wallet,wallet_to_bank,float_deposit,float_topup'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'amount' => ['required', 'numeric', 'min:1'],
            'provider_reference' => ['nullable', 'string', 'max:60'],
            'sms_id' => ['nullable', 'exists:sms_messages,id'],
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
            'Processed via manual form'.(isset($validated['sms_id']) ? ' (SMS #'.$validated['sms_id'].')' : ''),
            $validated['provider_reference'] ?? null,
        );

        // Associate with today's daily opening (if exists, e.g. for cashiers)
        if ($todayOpening) {
            $transaction->update(['daily_opening_id' => $todayOpening->id]);
        }

        // Link to SMS if provided (reference connect to the message as well)
        if (! empty($validated['sms_id'])) {
            $sms = SmsMessage::find($validated['sms_id']);
            if ($sms) {
                $sms->update([
                    'processing_status' => 'RECORDED',
                    'processing_error' => null,
                    'transaction_id' => $transaction->id,
                    'transaction_reference' => $transaction->provider_reference,
                    'amount' => $transaction->amount,
                    'transaction_type' => $transaction->type,
                    'customer_name' => $transaction->customer_name,
                    'customer_phone' => $transaction->customer_phone,
                    'network_id' => $transaction->network_id,
                ]);
            }
        } elseif (! empty($validated['provider_reference'])) {
            // Also link any existing SMS with same provider_reference if not yet linked
            SmsMessage::where('transaction_reference', $validated['provider_reference'])
                ->whereNull('transaction_id')
                ->update([
                    'processing_status' => 'RECORDED',
                    'transaction_id' => $transaction->id,
                ]);
        }

        $this->recordAudit('Transaction processed', 'Transaction', $transaction->id, [
            'reference' => $transaction->reference,
            'type' => $validated['type'],
            'amount' => $validated['amount'],
            'provider_reference' => $validated['provider_reference'] ?? null,
            'sms_id' => $validated['sms_id'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction processed successfully.', 'transaction_id' => $transaction->id, 'reference' => $transaction->reference]);
        }

        return redirect()->route('transactions.receipt', $transaction)->with('status', 'Transaction '.$transaction->reference.' created and linked'.(! empty($validated['sms_id']) ? ' to SMS #'.$validated['sms_id'] : '').'.');
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

        try {
            $pdf = Pdf::loadView('transactions.receipt-pdf', compact('transaction'));
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);

            return $pdf->download('receipt-'.$transaction->reference.'.pdf');
        } catch (\Throwable $e) {
            // Fallback to direct Dompdf if wrapper not available (e.g. production cache issue)
            $html = view('transactions.receipt-pdf', compact('transaction'))->render();
            $dompdf = new Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="receipt-'.$transaction->reference.'.pdf"',
            ]);
        }
    }

    public function reverse(Request $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
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
                // Reverse is opposite of process(): deposit/float_deposit -amount -> reverse +amount, withdrawal +amount -> reverse -amount
                $delta = match ($transaction->type) {
                    'deposit', 'float_deposit', 'float_topup', 'bank_to_wallet' => $amount,
                    'withdrawal' => -$amount,
                    default => $amount,
                };
                $balance->balance += $delta;
                $balance->save();
            }

            if (in_array($transaction->type, ['deposit', 'withdrawal', 'float_deposit', 'float_topup', 'bank_to_wallet', 'wallet_to_bank', 'airtime'], true)) {
                $direction = in_array($transaction->type, ['deposit', 'float_deposit', 'float_topup', 'bank_to_wallet', 'airtime'], true) ? -1 : 1;
                if ($transaction->type === 'wallet_to_bank') {
                    $direction = -1;
                }
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
                'reversal_reason' => $validated['reason'] ?? '',
            ]);

            $this->journals->reverseForTransaction($transaction, auth()->id());
        });

        $this->recordAudit('Transaction reversed', 'Transaction', $transaction->id, [
            'reference' => $transaction->reference,
            'reason' => $validated['reason'] ?? '',
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction reversed successfully.']);
        }

        return back()->with('status', 'Transaction reversed successfully.');
    }

    public function markUnusual(Request $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $transaction->markUnusual($validated['reason']);

        $this->recordAudit('Transaction marked unusual', 'Transaction', $transaction->id, [
            'reference' => $transaction->reference,
            'reason' => $validated['reason'],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction marked as unusual.']);
        }

        return back()->with('status', 'Transaction marked as unusual.');
    }

    public function clearUnusual(Request $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $transaction->markUnusual(null);

        $this->recordAudit('Transaction unusual flag cleared', 'Transaction', $transaction->id, [
            'reference' => $transaction->reference,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Unusual flag cleared.']);
        }

        return back()->with('status', 'Unusual flag cleared.');
    }

    /**
     * @return array{networks: array, types: array}
     */
    private function combos(): array
    {
        return [
            'networks' => Network::orderBy('name')->get(['id', 'name', 'color']),
            'types' => ['deposit', 'withdrawal', 'send_money', 'bill_payment', 'airtime', 'data', 'bank_to_wallet', 'wallet_to_bank', 'float_deposit'],
        ];
    }
}
