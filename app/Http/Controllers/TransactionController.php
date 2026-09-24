<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\CommissionRate;
use App\Models\DailyOpening;
use App\Models\JournalEntry;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Reconciliation;
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
use Illuminate\Support\Carbon;
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

        $todayOpening = null;
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
        } else {
            $todayOpening = DailyOpening::forAgentAndDate($agent->id, today())->first();
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

    public function edit(Request $request, Transaction $transaction): View
    {
        $transaction->load(['network', 'agent', 'operator', 'dailyOpening', 'smsMessages.device', 'smsMessages.network']);
        $combos = $this->combos();
        $agent = $transaction->agent ?? Agent::find($transaction->agent_id) ?? cash_point();

        $smsMessages = SmsMessage::with(['device', 'network'])
            ->where(function ($q) use ($transaction) {
                $q->whereNull('transaction_id')
                    ->orWhere('transaction_id', $transaction->id);
            })
            ->latest('server_received_at')
            ->limit(50)
            ->get();

        if (filled($transaction->provider_reference)) {
            $byRef = SmsMessage::with(['device', 'network'])
                ->where('transaction_reference', $transaction->provider_reference)
                ->whereNull('transaction_id')
                ->latest('server_received_at')
                ->limit(10)
                ->get();
            $smsMessages = $smsMessages->concat($byRef)->unique('id')->values();
        }

        $linkedSmsIds = $transaction->smsMessages->pluck('id')->map(fn ($id) => (string) $id)->toArray();
        $selectedSms = $request->input('sms') ?? ($linkedSmsIds[0] ?? null);

        $balances = $agent ? $agent->balances()->with('network')->get()->keyBy('network_id') : collect();
        $networkBalances = $combos['networks']->mapWithKeys(function ($n) use ($balances) {
            $b = $balances->get($n['id']);

            return [$n['id'] => $b ? (float) $b->balance : 0];
        });

        return view('transactions.edit', compact('transaction', 'combos', 'smsMessages', 'linkedSmsIds', 'selectedSms', 'networkBalances', 'agent'));
    }

    public function update(Request $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'network_id' => ['required', 'exists:networks,id'],
            'type' => ['required', 'in:deposit,withdrawal,send_money,bill_payment,airtime,data,bank_to_wallet,wallet_to_bank,float_deposit,float_topup'],
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'amount' => ['required', 'numeric', 'min:1'],
            'provider_reference' => ['nullable', 'string', 'max:60'],
            'notes' => ['nullable', 'string', 'max:255'],
            'sms_id' => ['nullable', 'exists:sms_messages,id'],
            'transaction_date' => ['nullable', 'date'],
        ]);

        if ($transaction->status === 'reversed') {
            $message = 'Cannot edit a reversed transaction. Reverse has already undone its financial effects.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        // Use the transaction's assigned agent (the area it belongs to), not the current cash_point()
        $agent = $transaction->agent;
        if ($agent === null) {
            $agent = Agent::find($transaction->agent_id) ?? cash_point();
        }

        if ($agent === null) {
            $message = 'Assigned cash point not found for this transaction.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $oldAmount = (float) $transaction->amount;
        $oldType = $transaction->type;
        $oldNetworkId = (int) $transaction->network_id;
        $oldAgentId = (int) $transaction->agent_id;
        $oldDailyOpeningId = $transaction->daily_opening_id;
        $oldCommission = (float) ($transaction->commission ?? 0);
        $oldStatus = $transaction->status;
        $oldReference = $transaction->reference;
        $oldProviderReference = $transaction->provider_reference;

        $newAmount = (float) $validated['amount'];
        $newType = $validated['type'];
        $newNetworkId = (int) $validated['network_id'];

        $newFee = $this->feeFor($newType, $newAmount);
        $newCommission = $this->commissionFor($agent, $newNetworkId, $newType, $newAmount);

        $oldCreatedAt = $transaction->created_at ? Carbon::parse($transaction->created_at) : now();
        $newCreatedAt = isset($validated['transaction_date']) && filled($validated['transaction_date'])
            ? Carbon::parse($validated['transaction_date'])
            : $oldCreatedAt->copy();
        $isFinancialChange = $oldAmount !== $newAmount || $oldType !== $newType || $oldNetworkId !== $newNetworkId;
        $isDateChange = $oldCreatedAt->format('Y-m-d H:i:s') !== $newCreatedAt->format('Y-m-d H:i:s');
        $isDateDayChange = $oldCreatedAt->format('Y-m-d') !== $newCreatedAt->format('Y-m-d');
        $oldDateStr = $oldCreatedAt->format('Y-m-d');
        $newDateStr = $newCreatedAt->format('Y-m-d');

        try {
            DB::transaction(function () use ($transaction, $oldAmount, $oldType, $oldNetworkId, $oldAgentId, $oldDailyOpeningId, $oldCommission, $oldStatus, $newAmount, $newType, $newNetworkId, $newFee, $newCommission, $validated, $isFinancialChange, $isDateChange, $isDateDayChange, $oldCreatedAt, $newCreatedAt, $oldDateStr, $newDateStr): void {
                $needsFinancialAdjustment = $oldStatus === 'completed' && ($isFinancialChange || $isDateDayChange);
                // Revert old financial effects if needed (amount/type/network or date day moved)
                if ($needsFinancialAdjustment) {
                    // Revert old financial effects from the assigned area
                    $oldDeltaFloat = $this->floatDelta($oldType, $oldAmount);
                    $oldBalance = NetworkBalance::where('agent_id', $oldAgentId)->where('network_id', $oldNetworkId)->lockForUpdate()->first();
                    if ($oldBalance) {
                        $oldBalance->balance = (float) $oldBalance->balance - $oldDeltaFloat;
                        $oldBalance->save();
                    }

                    $oldDeltaCash = $this->cashDelta($oldType, $oldAmount);
                    if ($oldDeltaCash !== 0) {
                        $agentOld = Agent::where('id', $oldAgentId)->lockForUpdate()->first();
                        if ($agentOld) {
                            $agentOld->cash_balance = (float) $agentOld->cash_balance - $oldDeltaCash;
                            $agentOld->save();
                        }
                    }

                    if ($oldDailyOpeningId) {
                        $opening = DailyOpening::where('id', $oldDailyOpeningId)->lockForUpdate()->first();
                        if ($opening) {
                            $opening->total_volume = max(0, (float) $opening->total_volume - $oldAmount);
                            $opening->total_commission = max(0, (float) $opening->total_commission - $oldCommission);
                            $opening->total_transactions = max(0, (int) $opening->total_transactions - 1);
                            $opening->save();
                        }
                    } elseif ($isDateDayChange) {
                        // No daily_opening_id but transaction was on old date — try to find opening for old date and decrement if exists
                        $oldOpeningByDate = DailyOpening::forAgentAndDate($oldAgentId, $oldCreatedAt)->first();
                        if ($oldOpeningByDate) {
                            $oldOpeningByDate->total_volume = max(0, (float) $oldOpeningByDate->total_volume - $oldAmount);
                            $oldOpeningByDate->total_commission = max(0, (float) $oldOpeningByDate->total_commission - $oldCommission);
                            $oldOpeningByDate->total_transactions = max(0, (int) $oldOpeningByDate->total_transactions - 1);
                            $oldOpeningByDate->save();
                        }
                    }
                }

                if ($needsFinancialAdjustment) {
                    // Apply new financial effects to the (same) assigned area — respects network and date change
                    $newDeltaFloat = $this->floatDelta($newType, $newAmount);
                    $newBalance = NetworkBalance::where('agent_id', $oldAgentId)->where('network_id', $newNetworkId)->lockForUpdate()->first();
                    if (! $newBalance) {
                        $newBalance = NetworkBalance::create([
                            'agent_id' => $oldAgentId,
                            'network_id' => $newNetworkId,
                            'opening_balance' => 0,
                            'balance' => 0,
                        ]);
                        // Re-fetch with lock
                        $newBalance = NetworkBalance::where('agent_id', $oldAgentId)->where('network_id', $newNetworkId)->lockForUpdate()->first();
                    }
                    if ($newBalance) {
                        $newBalance->balance = (float) $newBalance->balance + $newDeltaFloat;
                        $newBalance->save();
                    }

                    $newDeltaCash = $this->cashDelta($newType, $newAmount);
                    if ($newDeltaCash !== 0) {
                        $agentNew = Agent::where('id', $oldAgentId)->lockForUpdate()->first();
                        if ($agentNew) {
                            $agentNew->cash_balance = (float) $agentNew->cash_balance + $newDeltaCash;
                            $agentNew->save();
                        }
                    }

                    // Determine target opening for new date
                    $targetOpeningId = null;
                    $targetOpening = DailyOpening::forAgentAndDate($oldAgentId, $newCreatedAt)->first();
                    if ($targetOpening) {
                        $targetOpeningId = $targetOpening->id;
                    } elseif (! $isDateDayChange) {
                        // Same day — keep old opening linkage if exists
                        $targetOpeningId = $oldDailyOpeningId;
                        if (! $targetOpeningId) {
                            $todayOpening = DailyOpening::forAgentAndDate($oldAgentId, today())->first();
                            if ($todayOpening && ! $todayOpening->is_closed) {
                                $targetOpeningId = $todayOpening->id;
                            }
                        }
                    } else {
                        // Date changed to a day with no opening — leave unlinked (transaction will be counted via whereDate for reports/reconciliation)
                        $targetOpeningId = null;
                    }

                    if ($targetOpeningId) {
                        $opening = DailyOpening::where('id', $targetOpeningId)->lockForUpdate()->first();
                        if ($opening) {
                            $opening->total_volume = (float) $opening->total_volume + $newAmount;
                            $opening->total_commission = (float) $opening->total_commission + $newCommission;
                            $opening->total_transactions = (int) $opening->total_transactions + 1;
                            $opening->save();
                        }
                        // Ensure transaction keeps link to opening for new date
                        $transaction->daily_opening_id = $targetOpeningId;
                    } else {
                        // No opening for new date — ensure we clear old linkage if date moved
                        if ($isDateDayChange) {
                            $transaction->daily_opening_id = null;
                        }
                    }
                } elseif ($isFinancialChange && $oldStatus === 'completed') {
                    // This branch is now covered by needsFinancialAdjustment above; kept for safety but no-op
                }

                // Refresh assigned agent for running balances after adjustments
                $agentForRunning = Agent::find($oldAgentId);
                $runningCash = $agentForRunning ? (float) $agentForRunning->cash_balance : $transaction->running_cash_balance;
                $runningFloat = $agentForRunning ? (float) $agentForRunning->totalFloat() : $transaction->running_float_balance;

                $updates = [
                    'network_id' => $newNetworkId,
                    'type' => $newType,
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'],
                    'amount' => $newAmount,
                    'fee' => $newFee,
                    'commission' => $newCommission,
                    'provider_reference' => $validated['provider_reference'] ?? $transaction->provider_reference,
                    'notes' => $validated['notes'] ?? $transaction->notes,
                    'running_cash_balance' => $runningCash,
                    'running_float_balance' => $runningFloat,
                ];

                if ($isDateChange) {
                    $updates['created_at'] = $newCreatedAt;
                }

                $transaction->update($updates);

                // If date changed, also force created_at via query to bypass Eloquent touch guard
                if ($isDateChange) {
                    DB::table('transactions')->where('id', $transaction->id)->update(['created_at' => $newCreatedAt, 'updated_at' => now()]);
                    $transaction->refresh();
                }

                // SMS assignment / reference linking (admin edit) — affects SMS inbox as well
                if (array_key_exists('sms_id', $validated) && filled($validated['sms_id'])) {
                    $sms = SmsMessage::find((int) $validated['sms_id']);
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
                }
                // Auto-link any previously unlinked SMS that shares the new provider_reference
                $newProviderRef = $validated['provider_reference'] ?? $oldProviderReference;
                if (filled($newProviderRef) && $newProviderRef !== $oldProviderReference) {
                    SmsMessage::where('transaction_reference', $newProviderRef)
                        ->whereNull('transaction_id')
                        ->update([
                            'processing_status' => 'RECORDED',
                            'transaction_id' => $transaction->id,
                        ]);
                }

                // Journal handling: keep GL in sync with the assigned area's transaction
                // Rebuild if amount/type/network changed OR date (day) changed — entry_date must follow transaction date, and GL must reflect new values
                $needsJournalRebuild = ($isFinancialChange || $isDateDayChange) && $oldStatus === 'completed';
                if ($needsJournalRebuild) {
                    $existingJournal = JournalEntry::where('reference', $transaction->reference)->first();
                    if ($existingJournal) {
                        // Delete old lines and the entry itself, then re-post fresh so debits/credits match new amount
                        $existingJournal->lines()->delete();
                        $existingJournal->delete();
                    }
                    // Also clean up any prior reversal entries for this reference (e.g. RVS-... created earlier)
                    $reversals = JournalEntry::where('description', 'like', '%'.$transaction->reference.'%')
                        ->where('reference', 'like', 'RVS-%')
                        ->get();
                    foreach ($reversals as $rev) {
                        $rev->lines()->delete();
                        $rev->delete();
                    }

                    $transaction->refresh();
                    $transaction->load('network');
                    // Only post if still completed
                    if ($transaction->status === 'completed') {
                        app(TransactionJournalService::class)->postForTransaction($transaction);
                    }

                    // If date (day) changed, ensure journal entry_date follows new transaction date
                    if ($isDateDayChange && $transaction->status === 'completed') {
                        $journal = JournalEntry::where('reference', $transaction->reference)->first();
                        if ($journal && $journal->entry_date->format('Y-m-d') !== $newCreatedAt->format('Y-m-d')) {
                            $journal->update(['entry_date' => $newCreatedAt->toDateString()]);
                        }
                    }
                }

                // Auto-update reconciliation for affected dates so Reports/Reconciliation stay correct
                try {
                    if ($isDateDayChange) {
                        $this->recomputeReconciliationForAgentDate($oldAgentId, $oldDateStr);
                        if ($newDateStr !== $oldDateStr) {
                            $this->recomputeReconciliationForAgentDate($oldAgentId, $newDateStr);
                        }
                    } elseif ($isFinancialChange && $oldStatus === 'completed') {
                        $this->recomputeReconciliationForAgentDate($oldAgentId, $oldDateStr);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('Reconciliation auto-recompute failed after transaction edit', ['error' => $e->getMessage(), 'reference' => $oldReference]);
                }
            });
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to update transaction: '.$e->getMessage()], 422);
            }

            return back()->with('error', 'Failed to update transaction: '.$e->getMessage());
        }

        $this->recordAudit('Transaction updated', 'Transaction', $transaction->id, [
            'reference' => $oldReference,
            'old' => ['amount' => $oldAmount, 'type' => $oldType, 'network_id' => $oldNetworkId, 'date' => $oldDateStr],
            'new' => ['amount' => $newAmount, 'type' => $newType, 'network_id' => $newNetworkId, 'date' => $newDateStr],
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction updated successfully.', 'reference' => $transaction->reference]);
        }

        return redirect()->route('transactions.index')->with('status', 'Transaction '.$transaction->reference.' updated successfully. Balances for assigned area have been adjusted.');
    }

    public function destroy(Request $request, Transaction $transaction): JsonResponse|RedirectResponse
    {
        if ($transaction->status === 'reversed') {
            // Already reversed — just delete without double-reversing balances
            $reference = $transaction->reference;
            $oldAgentIdForRec = (int) $transaction->agent_id;
            $oldDateForRec = $transaction->created_at ? Carbon::parse($transaction->created_at)->format('Y-m-d') : null;
            DB::transaction(function () use ($transaction, $reference): void {
                $journal = JournalEntry::where('reference', $reference)->first();
                if ($journal) {
                    $journal->lines()->delete();
                    $journal->delete();
                }
                $reversals = JournalEntry::where('description', 'like', '%'.$reference.'%')->where('reference', 'like', 'RVS-%')->get();
                foreach ($reversals as $rev) {
                    $rev->lines()->delete();
                    $rev->delete();
                }
                $transaction->delete();
            });

            // Recompute reconciliation for that date (reversed tx was not counted, but clean anyway)
            if ($oldDateForRec) {
                try {
                    $this->recomputeReconciliationForAgentDate($oldAgentIdForRec, $oldDateForRec);
                } catch (\Throwable $e) {
                    \Log::warning('Reconciliation recompute after delete (reversed) failed', ['error' => $e->getMessage()]);
                }
            }

            $this->recordAudit('Transaction deleted (was reversed)', 'Transaction', $transaction->id, ['reference' => $reference]);

            if ($request->expectsJson()) {
                return response()->json(['success' => true, 'message' => 'Reversed transaction deleted.']);
            }

            return redirect()->route('transactions.index')->with('status', 'Reversed transaction deleted.');
        }

        $oldAmount = (float) $transaction->amount;
        $oldCommission = (float) ($transaction->commission ?? 0);
        $oldType = $transaction->type;
        $oldNetworkId = (int) $transaction->network_id;
        $oldAgentId = (int) $transaction->agent_id;
        $oldDailyOpeningId = $transaction->daily_opening_id;
        $oldReference = $transaction->reference;
        $oldDateStrForDelete = $transaction->created_at ? Carbon::parse($transaction->created_at)->format('Y-m-d') : null;

        try {
            DB::transaction(function () use ($transaction, $oldAmount, $oldCommission, $oldType, $oldNetworkId, $oldAgentId, $oldDailyOpeningId, $oldReference): void {
                // Revert financial effects from the assigned area
                $deltaFloat = $this->floatDelta($oldType, $oldAmount);
                $balance = NetworkBalance::where('agent_id', $oldAgentId)->where('network_id', $oldNetworkId)->lockForUpdate()->first();
                if ($balance) {
                    $balance->balance = (float) $balance->balance - $deltaFloat;
                    $balance->save();
                }

                $deltaCash = $this->cashDelta($oldType, $oldAmount);
                if ($deltaCash !== 0) {
                    $agent = Agent::where('id', $oldAgentId)->lockForUpdate()->first();
                    if ($agent) {
                        $agent->cash_balance = (float) $agent->cash_balance - $deltaCash;
                        $agent->save();
                    }
                }

                if ($oldDailyOpeningId) {
                    $opening = DailyOpening::where('id', $oldDailyOpeningId)->lockForUpdate()->first();
                    if ($opening) {
                        $opening->total_volume = max(0, (float) $opening->total_volume - $oldAmount);
                        $opening->total_commission = max(0, (float) $opening->total_commission - $oldCommission);
                        $opening->total_transactions = max(0, (int) $opening->total_transactions - 1);
                        $opening->save();
                    }
                }

                // Remove journal entries for this transaction (and any reversals) to keep GL consistent
                $journal = JournalEntry::where('reference', $oldReference)->first();
                if ($journal) {
                    $journal->lines()->delete();
                    $journal->delete();
                }
                $reversals = JournalEntry::where('description', 'like', '%'.$oldReference.'%')->where('reference', 'like', 'RVS-%')->get();
                foreach ($reversals as $rev) {
                    $rev->lines()->delete();
                    $rev->delete();
                }

                $transaction->delete();
            });

            // Recompute reconciliation for that date so Reports/Reconciliation reflect deletion
            if ($oldDateStrForDelete) {
                try {
                    $this->recomputeReconciliationForAgentDate($oldAgentId, $oldDateStrForDelete);
                } catch (\Throwable $e) {
                    \Log::warning('Reconciliation recompute after delete failed', ['error' => $e->getMessage()]);
                }
            }
        } catch (\Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete transaction: '.$e->getMessage()], 422);
            }

            return back()->with('error', 'Failed to delete transaction: '.$e->getMessage());
        }

        $this->recordAudit('Transaction deleted', 'Transaction', $transaction->id, [
            'reference' => $oldReference,
            'amount' => $oldAmount,
            'type' => $oldType,
            'agent_id' => $oldAgentId,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Transaction deleted and assigned area balances reversed.']);
        }

        return redirect()->route('transactions.index')->with('status', 'Transaction '.$oldReference.' deleted. Balances for assigned area have been reversed.');
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

        // Include SMS linked by provider reference (in case transaction_id wasn't set at ingest time)
        if (filled($transaction->provider_reference)) {
            $byRef = SmsMessage::query()
                ->where('transaction_reference', $transaction->provider_reference)
                ->whereNull('transaction_id')
                ->with(['device'])
                ->get();

            $transaction->setRelation(
                'smsMessages',
                $transaction->smsMessages->concat($byRef)->unique('id')->values()
            );
        }

        return view('transactions.receipt', compact('transaction'));
    }

    public function receiptPdf(Request $request, Transaction $transaction)
    {
        $raw = $request->route('transaction');
        if (is_numeric($raw) && (string) $raw === (string) $transaction->id) {
            return redirect()->route('transactions.receipt.pdf', $transaction);
        }

        $transaction->load(['network', 'agent', 'operator', 'reverser', 'dailyOpening', 'smsMessages.device']);

        if (filled($transaction->provider_reference)) {
            $byRef = SmsMessage::query()
                ->where('transaction_reference', $transaction->provider_reference)
                ->whereNull('transaction_id')
                ->with(['device'])
                ->get();

            $transaction->setRelation(
                'smsMessages',
                $transaction->smsMessages->concat($byRef)->unique('id')->values()
            );
        }

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
                // Reverse is opposite of process(): deposit/float_deposit -amount -> reverse +amount; withdrawal/bank_to_wallet +amount -> reverse -amount
                $delta = match ($transaction->type) {
                    'deposit', 'float_deposit', 'float_topup' => $amount,
                    'withdrawal', 'bank_to_wallet' => -$amount,
                    default => $amount,
                };
                $balance->balance += $delta;
                $balance->save();
            }

            if (in_array($transaction->type, ['deposit', 'withdrawal', 'float_deposit', 'float_topup', 'wallet_to_bank', 'airtime'], true)) {
                $direction = in_array($transaction->type, ['deposit', 'float_deposit', 'float_topup', 'airtime'], true) ? -1 : 1;
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
            'types' => ['deposit', 'withdrawal', 'send_money', 'bill_payment', 'airtime', 'data', 'bank_to_wallet', 'wallet_to_bank', 'float_deposit', 'float_topup'],
        ];
    }

    private function floatDelta(string $type, float $amount): float
    {
        return match ($type) {
            'deposit', 'float_deposit', 'float_topup' => -$amount,
            'withdrawal', 'bank_to_wallet' => $amount,
            default => -$amount,
        };
    }

    private function cashDelta(string $type, float $amount): float
    {
        if (! in_array($type, ['deposit', 'withdrawal', 'float_deposit', 'float_topup', 'wallet_to_bank', 'airtime'], true)) {
            return 0;
        }

        $direction = in_array($type, ['deposit', 'float_deposit', 'float_topup', 'airtime'], true) ? 1 : -1;
        if ($type === 'wallet_to_bank') {
            $direction = -1;
        }

        return $direction * $amount;
    }

    private function commissionFor(Agent $agent, int $networkId, string $type, float $amount): float
    {
        $rate = CommissionRate::where('network_id', $networkId)
            ->where('agent_level', $agent->agent_level)
            ->where('transaction_type', $type)
            ->where('is_active', true)
            ->first();

        return round($amount * (($rate?->rate ?? 0) / 100), 2);
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

    /**
     * Recompute a stored reconciliation for an agent+date after a transaction edit/delete.
     * Keeps Reports/Reconciliation in sync when transaction date/amount changes.
     * If no reconciliation exists for that date, nothing to do (future buildRun will be correct).
     */
    private function recomputeReconciliationForAgentDate(int $agentId, string $date): void
    {
        $reconciliation = Reconciliation::where('agent_id', $agentId)
            ->where('reconciliation_date', $date)
            ->first();

        if (! $reconciliation) {
            return;
        }

        $agent = Agent::find($agentId);
        if (! $agent) {
            return;
        }

        // Rebuild expected values using same logic as ReconciliationController::buildRun
        $dailyOpening = DailyOpening::forAgentAndDate($agentId, Carbon::parse($date))->first();

        $transactions = Transaction::query()
            ->where('agent_id', $agentId)
            ->whereDate('created_at', $date)
            ->where('status', 'completed')
            ->whereIn('type', ['deposit', 'float_deposit', 'withdrawal'])
            ->get();

        $depositsByNetwork = $transactions
            ->whereIn('type', ['deposit', 'float_deposit'])
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum('amount'));

        $withdrawalsByNetwork = $transactions
            ->where('type', 'withdrawal')
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum('amount'));

        $networks = Network::active()->orderBy('name')->get(['id', 'name', 'color']);
        $balances = $agent->balances()->get()->keyBy('network_id');

        $rows = $networks->map(function (Network $network) use ($dailyOpening, $balances, $depositsByNetwork, $withdrawalsByNetwork): array {
            $balance = $balances->get($network->id);
            $opening = $dailyOpening !== null
                ? $dailyOpening->getFloatOpening($network->id)
                : (float) ($balance?->opening_balance ?? 0);
            if ($opening == 0.0) {
                $opening = (float) ($balance?->balance ?? 0);
            }
            $deposits = (float) ($depositsByNetwork[$network->id] ?? 0);
            $withdrawals = (float) ($withdrawalsByNetwork[$network->id] ?? 0);

            return [
                'id' => $network->id,
                'name' => $network->name,
                'color' => $network->color,
                'opening' => $opening,
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'expected' => round($opening - $deposits + $withdrawals, 2),
            ];
        })->values()->all();

        $openingCash = $dailyOpening !== null
            ? (float) $dailyOpening->cash_opening
            : (float) ($agent->reconciliations()->where('reconciliation_date', '<', $date)->orderByDesc('reconciliation_date')->first()?->counted_cash ?? $agent->cash_balance);

        $cashDeposits = (float) $transactions->whereIn('type', ['deposit', 'float_deposit'])->sum('amount');
        $cashWithdrawals = (float) $transactions->where('type', 'withdrawal')->sum('amount');
        $expectedCash = round($openingCash + $cashDeposits - $cashWithdrawals, 2);
        $expectedFloat = round(array_sum(array_column($rows, 'expected')), 2);

        // Preserve counted values, recompute variances
        $countedCash = (float) $reconciliation->counted_cash;
        $countedFloatTotal = (float) collect($reconciliation->network_balances ?? [])->sum('counted');

        // Rebuild network_balances with new expected but same counted
        $oldBalances = collect($reconciliation->network_balances ?? [])->keyBy('network_id');
        $newNetworkBalances = collect($rows)->map(function (array $row) use ($oldBalances): array {
            $old = $oldBalances->get($row['id']);
            $counted = $old ? (float) ($old['counted'] ?? $row['expected']) : (float) $row['expected'];

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

        $countedFloatTotal = (float) collect($newNetworkBalances)->sum('counted');
        $cashVariance = round($countedCash - $expectedCash, 2);
        $floatVariance = round($countedFloatTotal - $expectedFloat, 2);
        $tieOut = round(($openingCash + array_sum(array_column($rows, 'opening'))) - ($countedCash + $countedFloatTotal), 2);

        $status = abs($cashVariance) < 0.005 && abs($floatVariance) < 0.005 && abs($tieOut) < 0.005
            ? 'reconciled'
            : 'variance';

        // If corrections exist that resolve variance, mark resolved
        $corrections = $reconciliation->corrections;
        if ($corrections->isNotEmpty()) {
            $channels = ['cash' => ['variance' => $cashVariance]];
            foreach ($newNetworkBalances as $b) {
                $channels['float.'.$b['network']] = ['variance' => $b['variance']];
            }
            $allResolved = true;
            foreach ($channels as $key => $ch) {
                $sum = 0.0;
                if ($key === 'cash') {
                    $sum = (float) $corrections->where('scope', 'cash')->sum(fn ($c) => $c->signedAmount());
                } else {
                    $netName = substr($key, 6);
                    $sum = (float) $corrections->where('scope', 'float')->filter(fn ($c) => $c->network?->name === $netName)->sum(fn ($c) => $c->signedAmount());
                }
                if (abs($ch['variance'] - $sum) >= 0.005) {
                    $allResolved = false;
                    break;
                }
            }
            if ($allResolved && $status === 'variance') {
                $status = 'resolved';
            }
        }

        $reconciliation->update([
            'opening_cash' => $openingCash,
            'cash_deposits' => $cashDeposits,
            'cash_withdrawals' => $cashWithdrawals,
            'expected_cash' => $expectedCash,
            'opening_float' => round(array_sum(array_column($rows, 'opening')), 2),
            'total_float' => $expectedFloat,
            'cash_variance' => $cashVariance,
            'float_variance' => $floatVariance,
            'tie_out' => $tieOut,
            'network_balances' => $newNetworkBalances,
            'status' => $status,
        ]);
    }
}
