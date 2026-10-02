<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\DailyOpening;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\Reconciliation;
use App\Models\ReconciliationCorrection;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ExportService;
use App\Services\SmsSender;
use App\Support\Shift;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Dompdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ReconciliationController extends Controller
{
    public function index(): View
    {
        $records = Reconciliation::with(['agent', 'reconciler'])->latest()->limit(120)->get();

        $openRecords = Reconciliation::where('status', 'open')->count();

        $pendingShifts = $this->pendingShifts();

        $totals = [
            'reconciled' => Reconciliation::where('status', 'reconciled')->count(),
            'open' => $openRecords + $pendingShifts->count(),
            'variance' => Reconciliation::where('status', 'variance')->count(),
            'resolved' => Reconciliation::where('status', 'resolved')->count(),
            'varianceAmount' => (float) Reconciliation::where('status', 'variance')->sum('cash_variance'),
        ];

        $networks = Network::orderBy('name')->get();

        $exportColumns = $this->exportColumns();
        $exportRoute = route('reconciliation.export');

        return view('reconciliation.index', compact('records', 'totals', 'networks', 'exportColumns', 'exportRoute') + [
            'pendingShifts' => $pendingShifts,
            // Kept for the templates that still expect a flat date list.
            'pendingDays' => $pendingShifts->pluck('date')->unique()->values(),
        ]);
    }

    /**
     * Business dates and shifts that have completed activity but no
     * reconciliation yet.
     *
     * Reconciliation is per (date, shift), so the list has to be per
     * (date, shift) too. Bucketing only by DATE(created_at) hid a real problem:
     * a night shift runs 20:00 to 07:59, so activity after midnight belongs to
     * the *previous* business date, and comparing only dates meant that
     * reconciling a morning shift made the whole day look done and left the
     * night shift's money never reconciled.
     *
     * The shift that is currently running is excluded: the till is still open,
     * so it cannot be counted and closed.
     *
     * @return Collection<int, array{date: string, shift: string, label: string, window: string, transactions: int, amount: float}>
     */
    private function pendingShifts(): Collection
    {
        // Grouping by day+hour keeps this to at most 24 rows per day instead of
        // loading every transaction. SUBSTR is used rather than HOUR() because
        // HOUR() is MySQL-only and the suite runs on SQLite.
        $buckets = Transaction::where('status', 'completed')
            ->selectRaw('DATE(created_at) as day, SUBSTR(created_at, 12, 2) as hour, COUNT(*) as cnt, SUM(amount) as amount')
            ->groupBy('day', 'hour')
            ->get();

        $shifts = [];

        foreach ($buckets as $bucket) {
            $at = Carbon::parse($bucket->day)->setTime((int) $bucket->hour, 0, 0);
            $shift = Shift::for($at);
            $key = $shift['date'].'|'.$shift['shift'];

            if (! isset($shifts[$key])) {
                $shifts[$key] = [
                    'date' => $shift['date'],
                    'shift' => $shift['shift'],
                    'transactions' => 0,
                    'amount' => 0.0,
                ];
            }

            $shifts[$key]['transactions'] += (int) $bucket->cnt;
            $shifts[$key]['amount'] += (float) $bucket->amount;
        }

        // A date is covered by its own shift, or by a full-day reconciliation.
        $reconciled = Reconciliation::get(['reconciliation_date', 'shift'])
            ->map(fn (Reconciliation $reconciliation): array => [
                Carbon::parse($reconciliation->reconciliation_date)->toDateString(),
                $reconciliation->shift ?: Shift::FULL,
            ]);

        $fullDays = $reconciled->filter(fn (array $pair): bool => $pair[1] === Shift::FULL)->pluck(0)->all();
        $covered = $reconciled->reject(fn (array $pair): bool => $pair[1] === Shift::FULL)->map(fn (array $pair): string => $pair[0].'|'.$pair[1])->all();

        // A catch-up runs from its own date through to now, so it also clears
        // every later date that is still waiting.
        $caughtUpFrom = $reconciled
            ->filter(fn (array $pair): bool => $pair[1] === Shift::CATCHUP)
            ->map(fn (array $pair): string => $pair[0])
            ->min();

        $current = Shift::current();

        return collect($shifts)
            ->reject(fn (array $shift, string $key): bool => in_array($shift['date'], $fullDays, true)
                || ($caughtUpFrom !== null && $shift['date'] >= $caughtUpFrom)
                || in_array($key, $covered, true)
                || ($shift['date'] === $current['date'] && $shift['shift'] === $current['shift']))
            ->map(function (array $shift): array {
                [$start, $end] = Shift::window($shift['date'], $shift['shift']);

                return [
                    'date' => $shift['date'],
                    'shift' => $shift['shift'],
                    'label' => Shift::label($shift['shift']),
                    'window' => $start->format('d M H:i').' – '.$end->format('d M H:i'),
                    'transactions' => $shift['transactions'],
                    'amount' => round((float) $shift['amount'], 2),
                ];
            })
            ->sortBy(fn (array $shift): string => $shift['date'].$shift['shift'])
            ->values();
    }

    public function approve(Request $request, Reconciliation $reconciliation): JsonResponse|RedirectResponse
    {
        if (! is_role('supervisor', 'admin')) {
            abort(403);
        }

        $agent = cash_point();

        if ($agent && (int) $reconciliation->agent_id !== (int) $agent->id && ! is_admin()) {
            abort(403);
        }

        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:500'],
        ]);

        $reconciliation->update([
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_note' => $validated['approval_note'] ?? null,
        ]);

        $this->recordAudit('Reconciliation approved', 'Reconciliation', $reconciliation->id);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Reconciliation approved. The team may proceed to the next shift.']);
        }

        return back()->with('status', 'Reconciliation approved. The team may proceed to the next shift.');
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
        $decrypted = $this->decryptDateParam($rawDate);
        $isPlain = $this->isPlainDate($rawDate);
        // Encrypt URL header for admin — plain 2026-09-22 -> eyJ... (like float)
        if ($isAdmin && $rawDate !== null && $decrypted === null && $isPlain) {
            try {
                Carbon::parse($rawDate)->toDateString();

                return redirect()->route('reconciliation.create', array_filter([
                    'date' => $this->encryptDateParam($rawDate),
                    'shift' => $request->input('shift'),
                ], fn ($v) => $v !== null));
            } catch (\Throwable) {
            }
        }
        $effectiveRaw = $decrypted ?? $rawDate;
        try {
            $viewDate = Carbon::parse($effectiveRaw)->toDateString();
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

        $current = Shift::current();
        $shift = Shift::isValid($request->input('shift'))
            ? $request->input('shift')
            : ($viewDate === $current['date'] ? $current['shift'] : Shift::FULL);

        $run = $this->buildRun($agent, $viewDate, $shift);

        $existing = Reconciliation::where('agent_id', $agent->id)
            ->where('reconciliation_date', $viewDate)
            ->where('shift', $shift)
            ->latest()
            ->first();

        $availableDates = DailyOpening::where('agent_id', $agent->id)
            ->orderByDesc('opening_date')
            ->limit(30)
            ->pluck('opening_date')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->values();

        // Load all opening data + transactions done for the selected shift window
        [$windowStart, $windowEnd] = Shift::window($viewDate, $shift);

        $dayOpening = DailyOpening::forAgentAndDate($agent->id, Carbon::parse($viewDate))
            ->whereIn('shift', [$shift, Shift::FULL])
            ->first();
        $dayTransactions = Transaction::with(['network', 'operator', 'agent'])
            ->where('agent_id', $agent->id)
            ->where(function ($q) use ($windowStart, $windowEnd) {
                $q->whereBetween('created_at', [$windowStart, $windowEnd])
                    ->orWhereBetween('reversed_at', [$windowStart, $windowEnd]);
            })
            ->whereNotIn('type', ['float_topup', 'float_deposit', 'cash_to_float'])
            ->latest()
            ->limit(100)
            ->get();

        $dayFloatTransactions = FloatTransaction::with(['network', 'operator'])
            ->where('agent_id', $agent->id)
            ->whereBetween('created_at', [$windowStart, $windowEnd])
            ->latest()
            ->limit(50)
            ->get();

        $selectedDateEncrypted = $isAdmin ? $this->encryptDateParam($viewDate) : $viewDate;

        return view('reconciliation.create', [
            'agent' => $agent,
            'run' => $run,
            'existing' => $existing,
            'selectedDate' => $viewDate,
            'selectedShift' => $shift,
            'selectedDateEncrypted' => $selectedDateEncrypted,
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
            'shift' => ['nullable', Rule::in(Shift::types())],
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'counted_floats' => ['nullable', 'array'],
            'counted_floats.*' => ['nullable', 'numeric', 'min:0'],
        ]);

        $shift = $validated['shift'] ?? Shift::FULL;

        $agent = cash_point();

        if ($agent === null) {
            $message = 'Set up the cash point first before reconciling.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('cash-point.index')->with('error', $message);
        }

        $run = $this->buildRun($agent, $validated['reconciliation_date'], $shift);

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
                'float_topups' => $row['float_topups'] ?? 0,
                'bank_ins' => $row['bank_ins'] ?? 0,
                'commission_received' => $row['commission_received'] ?? 0,
                'expected' => $row['expected'],
                'system' => $row['expected'],
                'counted' => $counted,
                'variance' => round($counted - $row['expected'], 2),
            ];
        })->values()->all();

        $countedFloatTotal = (float) collect($networkBalances)->sum('counted');

        $cashVariance = round($countedCash - $expectedCash, 2);
        $floatVariance = round($countedFloatTotal - $run['expectedFloat'], 2);

        // Tie-out: expected total vs counted total (float top-ups are bank-replenished, not customer activity — they are already in expectedFloat, so opening vs counted would be -3M on days with 3M top-up. Don't penalize tie-out for float injection; tie-out reflects sum of variances only)
        $tieOut = round(($run['expectedCash'] + $run['expectedFloat']) - ($countedCash + $countedFloatTotal), 2);

        $status = abs($cashVariance) < 0.005 && abs($floatVariance) < 0.005 && abs($tieOut) < 0.005
            ? 'reconciled'
            : 'variance';

        $record = Reconciliation::create([
            'agent_id' => $agent->id,
            'reconciliation_date' => $validated['reconciliation_date'],
            'shift' => $shift,
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

        $this->notifySupervisorsForApproval($record);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $status === 'reconciled'
                ? 'Reconciliation matched perfectly.'
                : 'Reconciliation saved with variance.']);
        }

        return back()->with('status', 'Reconciliation saved.');
    }

    /**
     * Notify active supervisors by SMS that a reconciliation needs their approval.
     */
    private function notifySupervisorsForApproval(Reconciliation $record): void
    {
        try {
            $sender = app(SmsSender::class);

            if (! $sender->isConfigured()) {
                return;
            }

            $date = Carbon::parse($record->reconciliation_date)->format('d M Y');
            $text = 'Reconciliation #'.$record->code.' for '.$date.' ('.$record->status.') awaits your approval. Please login and approve it.';

            User::query()
                ->where('role', 'supervisor')
                ->where('is_active', true)
                ->whereNotNull('phone')
                ->each(function (User $supervisor) use ($sender, $text): void {
                    try {
                        $sender->sendSingle($sender->normalizeRecipient((string) $supervisor->phone), $text);
                    } catch (\Throwable $exception) {
                        \Log::warning('Supervisor approval SMS failed', ['user_id' => $supervisor->id, 'error' => $exception->getMessage()]);
                    }
                });
        } catch (\Throwable $exception) {
            \Log::warning('Could not notify supervisors for reconciliation approval', ['error' => $exception->getMessage()]);
        }
    }

    public function show(Reconciliation $reconciliation): View
    {
        $reconciliation->load(['agent', 'reconciler', 'corrections.network', 'corrections.creator']);

        $channels = $this->settledChannels($reconciliation);

        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);

        $run = $this->runFromRecord($reconciliation);

        return view('reconciliation.show', compact('reconciliation', 'channels', 'networks', 'run'));
    }

    public function edit(Reconciliation $reconciliation): View
    {
        if ($reconciliation->is_locked) {
            abort(403, 'Reconciliation is locked and cannot be recorrected. Unlock first.');
        }
        $reconciliation->load(['agent']);
        $run = $this->runFromRecord($reconciliation);
        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);

        $canChangeShift = $this->canChangeShift($reconciliation);
        $shiftOptions = $this->shiftOptions($reconciliation->reconciliation_date->toDateString(), $reconciliation->shift);

        return view('reconciliation.edit', compact('reconciliation', 'run', 'networks', 'shiftOptions', 'canChangeShift'));
    }

    /**
     * Changing the shift re-bases a report that has already been counted, so it
     * is limited to administrators and is not allowed once a supervisor has
     * signed the report off.
     */
    private function canChangeShift(Reconciliation $reconciliation): bool
    {
        return is_admin() && $reconciliation->approved_at === null;
    }

    /**
     * The shift types offered on the edit form, with the window each covers.
     *
     * @return array<int, array{value: string, label: string, window: string}>
     */
    private function shiftOptions(string $date, string $current): array
    {
        return array_map(function (string $shift) use ($date, $current): array {
            [$start, $end] = Shift::window($date, $shift);

            return [
                'value' => $shift,
                'label' => Shift::label($shift),
                'window' => $start->format('d M H:i').' – '.$end->format('d M H:i'),
                'selected' => $shift === $current,
            ];
        }, Shift::types());
    }

    public function update(Request $request, Reconciliation $reconciliation): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'counted_cash' => ['required', 'numeric', 'min:0'],
            'counted_floats' => ['nullable', 'array'],
            'counted_floats.*' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        // A shift can only be re-based by an administrator; for anyone else the
        // submitted value is ignored rather than trusted.
        $previousShift = $reconciliation->shift ?: Shift::FULL;
        $newShift = $this->canChangeShift($reconciliation) && Shift::isValid($request->input('shift'))
            ? $request->input('shift')
            : $previousShift;
        $shiftChanged = $newShift !== $previousShift;

        $countedCash = (float) $validated['counted_cash'];
        $countedFloatsInput = $validated['counted_floats'] ?? [];

        if ($shiftChanged) {
            // The window changed, so the stored basis no longer describes the
            // report. Rebuild it from the transactions for the new window rather
            // than keeping figures that belong to the old one.
            $run = $this->buildRun(
                $reconciliation->agent,
                $reconciliation->reconciliation_date->toDateString(),
                $newShift
            );

            $networkBalances = collect($run['networks'])->map(function (array $row) use ($countedFloatsInput): array {
                $id = $row['id'] ?? null;
                $name = $row['name'] ?? 'Network';
                $expected = round((float) ($row['expected'] ?? 0), 2);

                // The form submits counted floats keyed by network name.
                $counted = (float) ($countedFloatsInput[$name] ?? $countedFloatsInput[$id] ?? $expected);

                return [
                    'network_id' => $id,
                    'network' => $name,
                    'opening' => round((float) ($row['opening'] ?? 0), 2),
                    'deposits' => round((float) ($row['deposits'] ?? 0), 2),
                    'withdrawals' => round((float) ($row['withdrawals'] ?? 0), 2),
                    'float_topups' => round((float) ($row['float_topups'] ?? 0), 2),
                    'bank_ins' => round((float) ($row['bank_ins'] ?? 0), 2),
                    'commission_received' => round((float) ($row['commission_received'] ?? 0), 2),
                    'expected' => $expected,
                    'system' => $expected,
                    'counted' => $counted,
                    'variance' => round($counted - $expected, 2),
                ];
            })->values()->all();

            $expectedCash = (float) $run['expectedCash'];
            $expectedFloat = (float) $run['expectedFloat'];
        } else {
            $run = $this->runFromRecord($reconciliation);
            $expectedCash = (float) $run['expectedCash'];
            $expectedFloat = (float) $run['expectedFloat'];

            // Map counted_floats by network name or id to handle both
            $networkBalances = collect($reconciliation->network_balances ?? [])->map(function (array $row) use ($countedFloatsInput) {
                $keyName = $row['network'] ?? null;
                $keyId = $row['network_id'] ?? null;
                $newCounted = null;
                if ($keyName && isset($countedFloatsInput[$keyName])) {
                    $newCounted = (float) $countedFloatsInput[$keyName];
                } elseif ($keyId && isset($countedFloatsInput[$keyId])) {
                    $newCounted = (float) $countedFloatsInput[$keyId];
                } else {
                    $newCounted = (float) ($row['counted'] ?? $row['expected'] ?? 0);
                }
                $expected = (float) ($row['expected'] ?? $row['system'] ?? 0);

                return array_merge($row, [
                    'counted' => $newCounted,
                    'variance' => round($newCounted - $expected, 2),
                ]);
            })->values()->all();
        }

        $countedFloatTotal = collect($networkBalances)->sum('counted');
        $cashVariance = round($countedCash - $expectedCash, 2);
        $floatVariance = round($countedFloatTotal - $expectedFloat, 2);
        $tieOut = round(($expectedCash + $expectedFloat) - ($countedCash + $countedFloatTotal), 2);
        $channels = $this->settledChannels($reconciliation);
        // Recompute status after update
        $anyVariance = abs($cashVariance) > 0.005 || abs($floatVariance) > 0.005 || abs($tieOut) > 0.005;
        // Also check corrections
        $status = $anyVariance ? 'variance' : 'reconciled';
        if ($anyVariance) {
            $remainingCash = $cashVariance - collect($reconciliation->corrections)->where('scope', 'cash')->sum(fn ($c) => $c->signedAmount());
            $allRemainingZero = abs($remainingCash) < 0.005;
            foreach ($networkBalances as $b) {
                $netName = $b['network'] ?? '';
                $variance = (float) ($b['variance'] ?? 0);
                $settled = (float) collect($reconciliation->corrections)->where('scope', 'float')->filter(fn ($c) => $c->network?->name === $netName)->sum(fn ($c) => $c->signedAmount());
                if (abs($variance - $settled) >= 0.005) {
                    $allRemainingZero = false;
                    break;
                }
            }
            if ($allRemainingZero) {
                $status = 'resolved';
            }
        }

        $changes = [
            'counted_cash' => $countedCash,
            'cash_variance' => $cashVariance,
            'float_variance' => $floatVariance,
            'tie_out' => $tieOut,
            'network_balances' => $networkBalances,
            'total_float' => $expectedFloat,
            'status' => $status,
            'notes' => $validated['notes'] ?? $reconciliation->notes,
        ];

        if ($shiftChanged) {
            // The basis changed, so the opening and expected columns have to be
            // rewritten too or the report would show a night shift's figures
            // under a catch-up heading.
            $changes += [
                'shift' => $newShift,
                'opening_cash' => round((float) $run['openingCash'], 2),
                'cash_deposits' => round((float) $run['cashDeposits'], 2),
                'cash_withdrawals' => round((float) $run['cashWithdrawals'], 2),
                'expected_cash' => $expectedCash,
                'opening_float' => round((float) $run['openingFloat'], 2),
            ];
        }

        $reconciliation->update($changes);

        // If reconciled, update live balances to counted
        if ($status === 'reconciled') {
            $agent = $reconciliation->agent;
            if ($agent) {
                $agent->update(['cash_balance' => $countedCash]);
                foreach ($networkBalances as $row) {
                    $bal = $agent->balances()->where('network_id', $row['network_id'] ?? null)->first();
                    if ($bal) {
                        $bal->update(['balance' => $row['counted']]);
                    } else {
                        // Try by name
                        $net = Network::where('name', $row['network'] ?? '')->first();
                        if ($net) {
                            $bal2 = $agent->balances()->where('network_id', $net->id)->first();
                            if ($bal2) {
                                $bal2->update(['balance' => $row['counted']]);
                            }
                        }
                    }
                }
            }
        }

        $this->recordAudit('Reconciliation recorrected full', 'Reconciliation', $reconciliation->id, [
            'counted_cash' => $countedCash,
            'status' => $status,
            'shift_from' => $previousShift,
            'shift_to' => $newShift,
            'shift_changed' => $shiftChanged,
        ]);

        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'message' => 'Reconciliation recorrected. Status: '.$status]);
        }

        return redirect()->route('reconciliation.show', $reconciliation)->with('status', 'Recorrected — status '.$status);
    }

    public function exportSingle(Request $request, Reconciliation $reconciliation)
    {
        $reconciliation->load(['agent', 'reconciler', 'corrections.network', 'corrections.creator']);

        $run = $this->runFromRecord($reconciliation);
        $channels = $this->settledChannels($reconciliation);

        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        // For excel fallback to generic list-style export of this single record
        if ($format === 'excel') {
            $export = app(ExportService::class);
            $columns = [
                ['key' => 'field', 'label' => 'Field'],
                ['key' => 'value', 'label' => 'Value'],
            ];
            $rows = collect([
                ['field' => 'Reconciliation', 'value' => $reconciliation->code],
                ['field' => 'Date', 'value' => $reconciliation->reconciliation_date->format('Y-m-d')],
                ['field' => 'Agent', 'value' => $reconciliation->agent?->code.' — '.$reconciliation->agent?->name],
                ['field' => 'Status', 'value' => ucfirst($reconciliation->status)],
                ['field' => 'Opening Cash', 'value' => money($run['openingCash'])],
                ['field' => 'Opening Float', 'value' => money($run['openingFloat'])],
                ['field' => 'Expected Cash', 'value' => money($run['expectedCash'])],
                ['field' => 'Counted Cash', 'value' => money($run['countedCash'])],
                ['field' => 'Cash Variance', 'value' => money($run['cashVariance'])],
                ['field' => 'Expected Float', 'value' => money($run['expectedFloat'])],
                ['field' => 'Counted Float', 'value' => money($run['countedFloat'])],
                ['field' => 'Float Variance', 'value' => money($run['countedFloat'] - $run['expectedFloat'])],
                ['field' => 'Tie-out', 'value' => money($run['tieOut'])],
            ]);

            return $export->excel('Reconciliation-'.$reconciliation->code, $columns, $rows);
        }

        // Full complete PDF report
        $exportService = app(ExportService::class);
        $business = $exportService->businessInfo();
        $generatedAt = now()->format('d M Y H:i');
        $generatedBy = auth()->user()?->name ?? 'System';

        // Load activity for this date for complete report
        $dayOpening = DailyOpening::forAgentAndDate($reconciliation->agent_id, Carbon::parse($reconciliation->reconciliation_date))->first();
        $dayTransactions = Transaction::with(['network'])
            ->where('agent_id', $reconciliation->agent_id)
            ->whereDate('created_at', $reconciliation->reconciliation_date)
            ->whereNotIn('type', ['float_topup', 'float_deposit'])
            ->latest()
            ->limit(100)
            ->get();
        $dayFloatTransactions = FloatTransaction::with(['network'])
            ->where('agent_id', $reconciliation->agent_id)
            ->whereDate('created_at', $reconciliation->reconciliation_date)
            ->latest()
            ->limit(50)
            ->get();

        $title = 'Reconciliation '.$reconciliation->code;
        $subtitle = $reconciliation->reconciliation_date->format('l, d M Y').' — '.$reconciliation->agent?->code.' — '.ucfirst($reconciliation->status);

        try {
            $pdf = Pdf::loadView('exports.reconciliation-pdf', compact('reconciliation', 'run', 'channels', 'business', 'generatedAt', 'generatedBy', 'title', 'subtitle', 'dayOpening', 'dayTransactions', 'dayFloatTransactions'));
            $pdf->setPaper('a4', 'landscape');
            $pdf->setOptions(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true, 'isPhpEnabled' => true]);

            return $pdf->download('reconciliation-'.$reconciliation->code.'-'.$reconciliation->reconciliation_date->format('Ymd').'.pdf');
        } catch (\Throwable $e) {
            $html = view('exports.reconciliation-pdf', compact('reconciliation', 'run', 'channels', 'business', 'generatedAt', 'generatedBy', 'title', 'subtitle', 'dayOpening', 'dayTransactions', 'dayFloatTransactions'))->render();
            $dompdf = new Dompdf(['isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'landscape');
            $dompdf->render();

            return response($dompdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="reconciliation-'.$reconciliation->code.'-'.$reconciliation->reconciliation_date->format('Ymd').'.pdf"',
            ]);
        }
    }

    public function createCorrection(Reconciliation $reconciliation): View
    {
        $reconciliation->load(['agent']);

        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);

        // Transactions with linked SMS messages for this reconciliation's date and agent — for autofill
        $linkedTransactions = Transaction::with(['network', 'smsMessages'])
            ->where('agent_id', $reconciliation->agent_id)
            ->whereDate('created_at', $reconciliation->reconciliation_date)
            ->whereHas('smsMessages')
            ->latest()
            ->limit(50)
            ->get();

        // Also include transactions for same date that have provider_reference linking to SMS even if transaction_id not set (via smsMessages relation already handles)
        // Fallback: also load recent transactions for that date regardless, to allow selection
        if ($linkedTransactions->isEmpty()) {
            $linkedTransactions = Transaction::with(['network', 'smsMessages'])
                ->where('agent_id', $reconciliation->agent_id)
                ->whereDate('created_at', $reconciliation->reconciliation_date)
                ->latest()
                ->limit(50)
                ->get();
        }

        return view('reconciliation.corrections.create', compact('reconciliation', 'networks', 'linkedTransactions'));
    }

    public function storeCorrection(Request $request, Reconciliation $reconciliation)
    {
        if ($reconciliation->is_locked) {
            $msg = 'Reconciliation is locked and cannot be corrected. Unlock first.';
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }
        try {
            $validated = $request->validate([
                'scope' => ['required', 'in:cash,float'],
                'network_id' => ['nullable', 'required_if:scope,float', 'exists:networks,id'],
                'type' => ['required', Rule::in(array_keys(ReconciliationCorrection::types()))],
                'reference' => ['required', 'string', 'max:120'],
                'amount' => ['required', 'numeric', 'min:0.01'],
                'notes' => ['nullable', 'string', 'max:1000'],
            ]);
        } catch (ValidationException $e) {
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $e->errors()], 422);
            }
            throw $e;
        }

        try {
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

            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => true, 'message' => 'Correction recorded.']);
            }

            return redirect()->route('reconciliation.show', $reconciliation)->with('status', 'Correction recorded.');
        } catch (\Throwable $e) {
            \Log::error('Correction store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString(), 'input' => $request->all()]);
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Failed to record correction: '.$e->getMessage()], 500);
            }

            return back()->with('error', 'Failed to record correction: '.$e->getMessage())->withInput();
        }
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

    public function destroy(Request $request, Reconciliation $reconciliation): JsonResponse|RedirectResponse
    {
        $agent = cash_point();
        if ($agent && (int) $reconciliation->agent_id !== (int) $agent->id && ! is_admin()) {
            abort(403);
        }

        $date = $reconciliation->reconciliation_date;

        try {
            $reconciliation->delete();

            $this->recordAudit('Reconciliation deleted', 'Reconciliation', $reconciliation->id, [
                'date' => $date,
            ]);

            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => true, 'message' => 'Reconciliation for '.$date.' deleted.']);
            }

            return redirect()->route('reconciliation.index')->with('status', 'Reconciliation for '.$date.' deleted.');
        } catch (\Throwable $e) {
            \Log::error('Reconciliation delete failed', ['error' => $e->getMessage(), 'id' => $reconciliation->id, 'date' => $date]);
            if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json(['success' => false, 'message' => 'Failed to delete: '.$e->getMessage()], 500);
            }

            return back()->with('error', 'Failed to delete: '.$e->getMessage());
        }
    }

    public function toggleLock(Request $request, Reconciliation $reconciliation): JsonResponse|RedirectResponse
    {
        $agent = cash_point();
        if ($agent && (int) $reconciliation->agent_id !== (int) $agent->id && ! is_admin()) {
            abort(403);
        }

        $reconciliation->update(['is_locked' => ! $reconciliation->is_locked]);

        $msg = $reconciliation->is_locked ? 'Reconciliation locked — will not change with any transaction.' : 'Reconciliation unlocked — will update with transactions.';

        $this->recordAudit($reconciliation->is_locked ? 'Reconciliation locked' : 'Reconciliation unlocked', 'Reconciliation', $reconciliation->id, ['date' => $reconciliation->reconciliation_date, 'is_locked' => $reconciliation->is_locked]);

        if ($request->expectsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['success' => true, 'message' => $msg, 'is_locked' => $reconciliation->is_locked]);
        }

        return back()->with('status', $msg);
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
     *         float_topups: float,
     *         bank_ins: float,
     *         commission_received: float,
     *         expected: float,
     *     }>,
     * }
     */
    private function buildRun(Agent $agent, string $date, string $shift = 'full'): array
    {
        // Show float opening for ALL networks (active + inactive) as requested
        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);

        [$windowStart, $windowEnd] = Shift::window($date, $shift);

        $dailyOpening = DailyOpening::forAgentAndDate($agent->id, Carbon::parse($date))
            ->whereIn('shift', [$shift, Shift::FULL])
            ->first();

        $transactions = Transaction::query()
            ->where('agent_id', $agent->id)
            ->whereBetween('created_at', [$windowStart, $windowEnd])
            ->where('status', 'completed')
            ->get();

        $depositsByNetwork = $transactions
            ->whereIn('type', ['deposit', 'airtime', 'send_money', 'float_deposit'])
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum('amount'));

        $withdrawalsByNetwork = $transactions
            ->where('type', 'withdrawal')
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum('amount'));

        $floatTopupsByNetwork = $transactions
            ->whereIn('type', ['float_topup', 'float_deposit', 'cash_to_float'])
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum(fn (Transaction $transaction): float => $transaction->type === 'cash_to_float'
                ? (float) $transaction->amount - (float) $transaction->commission
                : (float) $transaction->amount));

        $bankToWalletByNetwork = $transactions
            ->where('type', 'bank_to_wallet')
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum('amount'));

        // Commission payouts are already counted inside float_topups (each one also writes a dual
        // float_topup FloatTransaction). Broken out here for visibility only — never added to expected.
        $commissionByNetwork = $transactions
            ->where('type', 'commission_income')
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum('amount'));

        // FloatTransaction top-ups for that date (e.g. 3×1M on 2026-09-21) — float is bank-replenished, must be added to expected
        $floatTransactions = FloatTransaction::where('agent_id', $agent->id)
            ->whereBetween('created_at', [$windowStart, $windowEnd])
            ->get();
        $floatTxTopupsByNetwork = $floatTransactions
            ->whereIn('type', ['float_topup', 'cash_in', 'cash_to_float'])
            ->groupBy('network_id')
            ->map(fn ($group): float => (float) $group->sum(fn (FloatTransaction $floatTransaction): float => $floatTransaction->floatDelta()));
        $floatCashDelta = (float) $floatTransactions->sum(fn (FloatTransaction $floatTransaction): float => $floatTransaction->cashDelta());

        $balances = $agent->balances()->get()->keyBy('network_id');
        $prevDateForFloat = Carbon::parse($date)->subDay()->toDateString();
        $prevReconciliationForFloat = $agent->reconciliations()->where('reconciliation_date', $prevDateForFloat)->latest()->first();

        $rows = $networks->map(function (Network $network) use ($dailyOpening, $balances, $depositsByNetwork, $withdrawalsByNetwork, $floatTopupsByNetwork, $bankToWalletByNetwork, $commissionByNetwork, $floatTxTopupsByNetwork, $prevReconciliationForFloat): array {
            $balance = $balances->get($network->id);

            // Direct search for previous day Counted to avoid id/name map mismatches (Vodacom 0 vs HaloPesa 1,086,000)
            $prevCounted = null;
            if ($prevReconciliationForFloat && ! empty($prevReconciliationForFloat->network_balances)) {
                $prevRow = collect($prevReconciliationForFloat->network_balances)->firstWhere('network_id', $network->id);
                if (! $prevRow) {
                    $prevRow = collect($prevReconciliationForFloat->network_balances)->firstWhere('network', $network->name);
                }
                if ($prevRow) {
                    $prevCounted = (float) ($prevRow['counted'] ?? $prevRow['expected'] ?? $prevRow['system'] ?? 0);
                }
            }

            $opening = null;
            if ($dailyOpening !== null) {
                $opening = $dailyOpening->getFloatOpening($network->id);
                if ($opening == 0.0 && $prevCounted !== null) {
                    $opening = $prevCounted;
                } elseif ($opening == 0.0 && $prevCounted === null) {
                    $opening = 0.0;
                }
            } else {
                if ($prevCounted !== null) {
                    $opening = $prevCounted;
                } else {
                    $opening = (float) ($balance?->opening_balance ?? 0);
                    if ($opening == 0.0) {
                        $opening = (float) ($balance?->balance ?? 0);
                    }
                }
            }

            $deposits = (float) ($depositsByNetwork[$network->id] ?? 0);
            $withdrawals = (float) ($withdrawalsByNetwork[$network->id] ?? 0);
            $floatTopups = (float) ($floatTopupsByNetwork[$network->id] ?? 0) + (float) ($floatTxTopupsByNetwork[$network->id] ?? 0);
            $bankIns = (float) ($bankToWalletByNetwork[$network->id] ?? 0);
            $commissionReceived = (float) ($commissionByNetwork[$network->id] ?? 0);

            // Float expected: opening - deposits + withdrawals + float top-ups + bank in
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
                'commission_received' => $commissionReceived,
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
        if ($floatCashDelta > 0) {
            $cashIn += $floatCashDelta;
        } elseif ($floatCashDelta < 0) {
            $cashOut += abs($floatCashDelta);
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
        if (! in_array($type, ['deposit', 'withdrawal', 'wallet_to_bank', 'airtime', 'send_money', 'float_deposit', 'cash_to_float'], true)) {
            return 0;
        }

        $direction = in_array($type, ['deposit', 'airtime', 'send_money', 'float_deposit'], true) ? 1 : -1;

        return $direction * $amount;
    }

    private function floatDelta(string $type, float $amount, float $commission = 0.0): float
    {
        return match ($type) {
            'deposit', 'airtime', 'send_money' => -$amount,
            'withdrawal', 'bank_to_wallet', 'float_topup', 'float_deposit', 'commission_income' => $amount,
            'cash_to_float' => $amount - $commission,
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
            $opening = (float) ($row['opening'] ?? 0);
            $deposits = (float) ($row['deposits'] ?? 0);
            $withdrawals = (float) ($row['withdrawals'] ?? 0);
            $floatTopups = (float) ($row['float_topups'] ?? 0);
            $bankIns = (float) ($row['bank_ins'] ?? 0);
            // Backfill for historic records saved before float_topups was stored: infer top-ups so Opening - deposits + withdrawals + top-ups = Expected
            if (abs($floatTopups) < 0.005 && abs($bankIns) < 0.005) {
                $inferred = round($expected - ($opening - $deposits + $withdrawals), 2);
                if (abs($inferred) > 0.005) {
                    $floatTopups = $inferred;
                }
            }
            // Fix any historic Vodacom (or any network) with negative opening due to live -356,500 fallback when no activity — show 0 when no data
            $hasNoActivity = abs($deposits) < 0.005 && abs($withdrawals) < 0.005 && abs($floatTopups) < 0.005 && abs($bankIns) < 0.005;
            if ($hasNoActivity && abs($counted) < 0.005) {
                // If opening was live negative due to earlier bug, correct to 0
                if ($opening < -1000 || abs($expected + 356500) < 0.005) {
                    $opening = 0.0;
                    $expected = 0.0;
                } elseif (abs($opening) > 0.005 && $row['network'] === 'Vodacom M-Pesa') {
                    // Specific Vodacom historic -356,500 case
                    if (abs($opening + 356500) < 0.005) {
                        $opening = 0.0;
                        $expected = 0.0;
                    }
                }
            }

            return [
                'network' => $row['network'] ?? 'Network',
                'opening' => $opening,
                'deposits' => $deposits,
                'withdrawals' => $withdrawals,
                'float_topups' => $floatTopups,
                'bank_ins' => $bankIns,
                'commission_received' => (float) ($row['commission_received'] ?? 0),
                'expected' => $expected,
                'counted' => $counted,
                'variance' => round($counted - $expected, 2),
            ];
        })->values()->all();

        $countedFloat = (float) collect($reconciliation->network_balances ?? [])->sum('counted');
        $expectedFloat = (float) $reconciliation->total_float;
        $expectedCash = (float) $reconciliation->expected_cash;
        $countedCash = (float) $reconciliation->counted_cash;
        // Recompute tie-out as Expected - Counted so historic -3M (opening - counted) records now show 0 when variances are 0 (top-ups already in expected)
        $recomputedTieOut = round(($expectedCash + $expectedFloat) - ($countedCash + $countedFloat), 2);

        return [
            'openingCash' => (float) $reconciliation->opening_cash,
            'cashDeposits' => (float) $reconciliation->cash_deposits,
            'cashWithdrawals' => (float) $reconciliation->cash_withdrawals,
            'expectedCash' => $expectedCash,
            'countedCash' => $countedCash,
            'cashVariance' => (float) $reconciliation->cash_variance,
            'openingFloat' => (float) $reconciliation->opening_float,
            'expectedFloat' => $expectedFloat,
            'countedFloat' => $countedFloat,
            'tieOut' => $recomputedTieOut,
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
            $system = (float) ($balance['system'] ?? $balance['expected'] ?? 0);
            $counted = (float) ($balance['counted'] ?? 0);
            $opening = (float) ($balance['opening'] ?? 0);
            $deposits = (float) ($balance['deposits'] ?? 0);
            $withdrawals = (float) ($balance['withdrawals'] ?? 0);
            $floatTopups = (float) ($balance['float_topups'] ?? 0);
            $bankIns = (float) ($balance['bank_ins'] ?? 0);
            // Correct historic Vodacom -356,500 with no activity to 0/0 for display (was live fallback)
            $hasNoActivity = abs($deposits) < 0.005 && abs($withdrawals) < 0.005 && abs($floatTopups) < 0.005 && abs($bankIns) < 0.005;
            if ($hasNoActivity && abs($counted) < 0.005 && (abs($opening + 356500) < 0.005 || abs($system + 356500) < 0.005)) {
                $system = 0.0;
                $opening = 0.0;
            }
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

    private function encryptDateParam(string $date): string
    {
        try {
            return Crypt::encryptString($date);
        } catch (\Throwable) {
            return $date;
        }
    }

    private function decryptDateParam(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            $dec = Crypt::decryptString($value);
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dec)) {
                return $dec;
            }
        } catch (\Throwable) {
        }

        return null;
    }

    private function isPlainDate(?string $value): bool
    {
        if (! $value) {
            return false;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
