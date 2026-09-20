<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\DailyOpening;
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

        // A cash point is only "configured" when it can actually operate.
        // Otherwise show the set-up form instead of a page full of blanks.
        if ($agent === null || empty($agent->code) || empty($agent->name) || empty($agent->phone)) {
            return view('cash_point.setup', ['agent' => $agent]);
        }

        $agent->load('balances.network');

        $recentTransactions = Transaction::with(['network', 'operator'])
            ->where('agent_id', $agent->id)
            ->latest()
            ->limit(12)
            ->get();

        $todayOpening = DailyOpening::forAgentAndDate($agent->id, today())->first();
        $isOpeningDone = $todayOpening !== null;

        $summary = [
            'cash' => (float) $agent->cash_balance,
            'float' => agent_total_float($agent),
            'volume' => (float) $agent->transactions()->where('status', 'completed')->sum('amount'),
            'count' => $agent->transactions()->count(),
        ];

        // Today's running totals (if opening done)
        $todayStats = null;
        if ($isOpeningDone) {
            $todayTxns = Transaction::where('agent_id', $agent->id)
                ->whereDate('created_at', today())
                ->where('status', 'completed')
                ->get();

            $todayStats = [
                'volume' => (float) $todayTxns->sum('amount'),
                'commission' => (float) $todayTxns->sum('commission'),
                'count' => $todayTxns->count(),
                'cash_opening' => (float) $todayOpening->cash_opening,
                'float_opening' => (float) array_sum($todayOpening->float_openings ?? []),
                'cash_current' => (float) $agent->cash_balance,
                'float_current' => agent_total_float($agent),
                'is_closed' => $todayOpening->is_closed,
            ];
        }

        return view('cash_point.index', compact('agent', 'recentTransactions', 'summary', 'todayOpening', 'isOpeningDone', 'todayStats'));
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $agent = cash_point();

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:agents,code'.($agent?->id ? ','.$agent->id : '')],
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

        if ($agent === null) {
            $agent = Agent::create($validated);
            $message = 'Cash point set up successfully.';
            $this->recordAudit('Cash point set up', 'Agent', $agent->id, ['name' => $agent->name]);
        } else {
            $agent->update($validated);
            $message = 'Cash point updated successfully.';
            $this->recordAudit('Cash point updated', 'Agent', $agent->id, ['name' => $agent->name]);
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()->route('cash-point.index')->with('status', $message);
    }
}
