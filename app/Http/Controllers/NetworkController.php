<?php

namespace App\Http\Controllers;

use App\Models\CommissionRate;
use App\Models\Network;
use App\Models\NetworkBalance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $types = ['deposit', 'withdrawal', 'send_money', 'bill_payment', 'airtime', 'data', 'bank_to_wallet'];

        return view('networks.index', compact('networks', 'totalFloat', 'rates', 'types') + ['activeType' => $activeType]);
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
