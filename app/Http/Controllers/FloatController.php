<?php

namespace App\Http\Controllers;

use App\Models\DailyOpening;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Services\ExportService;
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

        $todayOpening = DailyOpening::forAgentAndDate($cashPoint->id, today())->first();

        if (! $todayOpening) {
            return redirect()->route('daily-opening.create')->with('error', 'Record daily opening first before managing float.');
        }

        if ($todayOpening->is_closed) {
            return redirect()->route('daily-opening.show', $todayOpening)->with('error', 'Daily session is already closed. Cannot manage float.');
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
            ->where('agent_id', $cashPoint->id)
            ->latest()
            ->limit(50)
            ->get();

        $networks = Network::active()->pluck('name', 'id');

        $exportColumns = $this->exportColumns();
        $exportRoute = route('float.export');

        return view('float.index', compact('balances', 'floatTransactions', 'networks', 'summary', 'todayOpening', 'exportColumns', 'exportRoute'));
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

    public function create(): View|RedirectResponse
    {
        $cashPoint = cash_point();

        if ($cashPoint === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first before managing float.');
        }

        $todayOpening = DailyOpening::forAgentAndDate($cashPoint->id, today())->first();

        if (! $todayOpening) {
            return redirect()->route('daily-opening.create')->with('error', 'Record daily opening first before managing float.');
        }

        if ($todayOpening->is_closed) {
            return redirect()->route('daily-opening.show', $todayOpening)->with('error', 'Daily session is already closed. Cannot manage float.');
        }

        $networks = Network::active()->pluck('name', 'id');

        return view('float.create', compact('networks'));
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
            // For supervisor/admin, get today's opening if exists
            $todayOpening = DailyOpening::forAgentAndDate($agent->id, today())->first();
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
                'daily_opening_id' => $todayOpening?->id,
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
