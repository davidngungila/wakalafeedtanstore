<?php

namespace App\Http\Controllers;

use App\Models\CommissionRate;
use App\Models\Device;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Services\ExportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NetworkController extends Controller
{
    public function index(Request $request): View
    {
        $activeType = $request->input('type', 'all');

        $networks = Network::withCount('transactions')
            ->get()
            ->map(function (Network $network) {
                return [
                    'id' => $network->id,
                    'route_key' => $network->getRouteKey(),
                    'name' => $network->name,
                    'code' => $network->code,
                    'color' => $network->color,
                    'is_active' => $network->is_active,
                    'transactions_count' => $network->transactions_count,
                    'float' => (float) $network->balances()->sum('balance'),
                    'volume' => (float) $network->transactions()->where('status', 'completed')->sum('amount'),
                    'commission' => (float) $network->transactions()->where('status', 'completed')->sum('commission'),
                ];
            });

        $totalFloat = (float) NetworkBalance::sum('balance');

        $rates = CommissionRate::with('network')
            ->when($activeType !== 'all', fn ($q) => $q->where('transaction_type', $activeType))
            ->orderBy('network_id')
            ->orderBy('transaction_type')
            ->get();

        $types = ['deposit', 'withdrawal', 'send_money', 'bill_payment', 'airtime', 'data', 'bank_to_wallet', 'cash_to_float'];

        $exportColumns = $this->exportColumns();
        $exportRoute = route('networks.export');

        return view('networks.index', compact('networks', 'totalFloat', 'rates', 'types', 'exportColumns', 'exportRoute') + ['activeType' => $activeType]);
    }

    public function export(Request $request, ExportService $export)
    {
        $available = $this->exportColumns();
        $columns = $export->resolveColumns($available, $request->input('columns'));
        $format = in_array($request->input('format', 'pdf'), ['pdf', 'excel'], true) ? $request->input('format') : 'pdf';

        $rows = Network::withCount('transactions')->get()->map(fn (Network $n) => [
            'name' => $n->name,
            'code' => $n->code,
            'status' => $n->is_active ? 'Active' : 'Suspended',
            'transactions' => $n->transactions_count,
            'float' => money($n->balances()->sum('balance')),
            'volume' => money($n->transactions()->where('status', 'completed')->sum('amount')),
            'commission' => money($n->transactions()->where('status', 'completed')->sum('commission')),
        ])->map(fn (array $row) => collect($columns)->mapWithKeys(fn ($col) => [$col['key'] => $row[$col['key']] ?? ''])->all());

        $title = 'Networks Report';
        $subtitle = 'Generated '.now()->format('d M Y H:i').' — '.$rows->count().' records';

        if ($format === 'excel') {
            return $export->excel($title, $columns, $rows);
        }

        return $export->pdf($title, $subtitle, $columns, $rows);
    }

    private function exportColumns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'code', 'label' => 'Code'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'transactions', 'label' => 'Transactions'],
            ['key' => 'float', 'label' => 'Float'],
            ['key' => 'volume', 'label' => 'Volume'],
            ['key' => 'commission', 'label' => 'Commission'],
        ];
    }

    public function show(Request $request, Network $network): View
    {
        $manageable = is_role('supervisor', 'admin');
        $activeTab = $request->input('tab', 'transactions');
        $activeTab = in_array($activeTab, ['transactions', 'messages', 'devices'], true) ? $activeTab : 'transactions';

        if ($activeTab === 'devices' && ! $manageable) {
            $activeTab = 'transactions';
        }

        $query = Transaction::with(['agent', 'operator'])
            ->where('network_id', $network->id);

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('type') && $request->input('type') !== 'all') {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        if ($request->filled('q')) {
            $needle = $request->input('q');
            $query->where(function ($sub) use ($needle) {
                $sub->where('reference', 'like', "%{$needle}%")
                    ->orWhere('provider_reference', 'like', "%{$needle}%")
                    ->orWhere('customer_name', 'like', "%{$needle}%")
                    ->orWhere('customer_phone', 'like', "%{$needle}%");
            });
        }

        $transactions = $query->latest()->limit(200)->get();

        $completed = fn ($inner) => $inner->where('status', 'completed');

        $stats = [
            'float' => (float) $network->balances()->sum('balance'),
            'volume' => (float) $completed($network->transactions())->sum('amount'),
            'commission' => (float) $completed($network->transactions())->sum('commission'),
            'count' => (int) $network->transactions()->count(),
            'today_volume' => (float) $completed($network->transactions())->whereDate('created_at', today())->sum('amount'),
            'today_count' => (int) $network->transactions()->whereDate('created_at', today())->count(),
            'failed' => (int) $network->transactions()->whereIn('status', ['failed', 'reversed'])->count(),
        ];

        $messages = collect();
        if ($manageable && in_array($activeTab, ['messages', 'transactions'], true)) {
            $messages = SmsMessage::with(['device', 'transaction'])
                ->where('network_id', $network->id)
                ->latest('server_received_at')
                ->limit(200)
                ->get();
        }

        $devices = collect();
        if ($manageable && in_array($activeTab, ['devices', 'transactions'], true)) {
            $devices = Device::with(['agent', 'networks', 'lines.network'])
                ->where(function ($q) use ($network) {
                    $q->whereHas('networks', fn ($w) => $w->whereKey($network->id))
                        ->orWhere('network_id', $network->id);
                })
                ->orderBy('name')
                ->get();
        }

        $types = ['deposit', 'withdrawal', 'send_money', 'bill_payment', 'airtime', 'data', 'bank_to_wallet', 'wallet_to_bank', 'cash_to_float'];

        return view('networks.show', [
            'network' => $network,
            'transactions' => $transactions,
            'stats' => $stats,
            'types' => $types,
            'filters' => $request->only(['status', 'type', 'from', 'to', 'q']),
            'messages' => $messages,
            'devices' => $devices,
            'activeTab' => $activeTab,
            'manageable' => $manageable,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', 'unique:networks,code'],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $network = Network::create(array_merge($validated, ['is_active' => $request->boolean('is_active')]));

        $this->recordAudit('Network created', 'Network', $network->id, ['name' => $network->name]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Network added successfully.']);
        }

        return back()->with('status', 'Network added successfully.');
    }

    public function update(Request $request, Network $network): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:30', 'unique:networks,code,'.$network->id],
            'color' => ['nullable', 'string', 'max:20'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $network->update($validated);

        $this->recordAudit('Network updated', 'Network', $network->id, ['name' => $network->name]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Network updated successfully.']);
        }

        return back()->with('status', 'Network updated successfully.');
    }

    public function destroy(Request $request, Network $network): JsonResponse|RedirectResponse
    {
        // Special handling for MIX BY YAS AND MPESA - allow deletion with SMS records (user requested pop-up modal)
        $isMixMpesa = str_contains(strtoupper($network->name), 'MIX') && str_contains(strtoupper($network->name), 'MPESA')
            || $network->code === 'HALOPESA' || $network->code === 'MIXX'
            || strtoupper($network->name) === 'MIX BY YAS AND MPESA';

        $forceDeleteSms = $request->boolean('force_delete_sms') || $isMixMpesa;

        if (! $forceDeleteSms) {
            if ($network->transactions()->exists()) {
                $message = 'Cannot delete '.$network->name.': transactions exist for this network. Suspend it instead.';

                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return back()->withErrors(['network' => $message]);
            }

            if (FloatTransaction::where('network_id', $network->id)->exists()) {
                $message = 'Cannot delete '.$network->name.': float transactions exist for this network. Suspend it instead.';

                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => $message], 422);
                }

                return back()->withErrors(['network' => $message]);
            }
        }

        $name = $network->name;
        $code = $network->code;

        DB::transaction(function () use ($network, $forceDeleteSms) {
            // For MIX BY YAS AND MPESA, also delete all SMS records (pop-up modal says This cannot be undone)
            if ($forceDeleteSms) {
                SmsMessage::where('network_id', $network->id)->delete();
                // Also delete transactions and float for this network when force deleting (to allow complete removal)
                Transaction::where('network_id', $network->id)->delete();
                FloatTransaction::where('network_id', $network->id)->delete();
                NetworkBalance::where('network_id', $network->id)->delete();
                CommissionRate::where('network_id', $network->id)->delete();
            }
            $network->devices()->detach();
            $network->delete();
        });

        $this->recordAudit('Network deleted', 'Network', null, ['name' => $name, 'code' => $code]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Network '.$name.' deleted.']);
        }

        return redirect()->route('networks.index')->with('status', 'Network '.$name.' deleted.');
    }

    public function updateRates(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'rates' => ['required', 'array'],
            'rates.*.id' => ['required', 'exists:commission_rates,id'],
            'rates.*.rate' => ['nullable', 'numeric', 'min:0'],
            'rates.*.is_active' => ['nullable', 'boolean'],
        ]);

        foreach ($validated['rates'] as $rateInput) {
            CommissionRate::whereKey($rateInput['id'])->update([
                'rate' => $rateInput['rate'] ?? 0,
                'is_active' => (bool) ($rateInput['is_active'] ?? true),
            ]);
        }

        $this->recordAudit('Commission rates updated', 'CommissionRate', null, ['items' => count($validated['rates'])]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Commission rates updated successfully.']);
        }

        return back()->with('status', 'Commission rates updated successfully.');
    }
}
