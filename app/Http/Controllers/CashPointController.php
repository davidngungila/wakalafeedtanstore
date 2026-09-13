<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashPointController extends Controller
{
    public function index(): View
    {
        $agent = cash_point();
        $agent->load('balances.network');

        $recentTransactions = Transaction::with('network')
            ->where('agent_id', $agent->id)
            ->latest()
            ->limit(12)
            ->get();

        $summary = [
            'cash' => (float) $agent->cash_balance,
            'float' => agent_total_float($agent),
            'volume' => (float) $agent->transactions()->where('status', 'completed')->sum('amount'),
            'count' => $agent->transactions()->count(),
        ];

        return view('cash_point.index', compact('agent', 'recentTransactions', 'summary'));
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $agent = cash_point();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:agents,code,'.$agent->id],
            'name' => ['required', 'string', 'max:120'],
            'owner_name' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'national_id' => ['nullable', 'string', 'max:40'],
            'region' => ['nullable', 'string', 'max:80'],
            'district' => ['nullable', 'string', 'max:80'],
            'ward' => ['nullable', 'string', 'max:80'],
            'street' => ['nullable', 'string', 'max:120'],
            'agent_level' => ['required', 'in:bronze,silver,gold,platinum'],
            'status' => ['required', 'in:active,suspended,inactive'],
        ]);

        $agent->update($validated);

        $this->recordAudit('Cash point updated', 'Agent', $agent->id, ['name' => $agent->name]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Cash point updated successfully.']);
        }

        return redirect()->route('cash-point.index')->with('status', 'Cash point updated successfully.');
    }
}
