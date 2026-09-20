<?php

namespace App\Http\Controllers;

use App\Models\DailyOpening;
use App\Models\Network;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $report = $request->input('report', 'daily');

        $agent = cash_point();
        $agentId = $agent?->id;

        $daily = collect(range(6, 0))->map(function (int $daysAgo) use ($agentId) {
            $day = today()->subDays($daysAgo);

            $opening = null;
            if ($agentId !== null) {
                $opening = DailyOpening::forAgentAndDate($agentId, $day)->first();
            }

            $txnQuery = Transaction::whereDate('created_at', $day)->where('status', 'completed');
            if ($agentId !== null) {
                $txnQuery->where('agent_id', $agentId);
            }

            $completedTxns = (clone $txnQuery)->get();
            $fees = (float) $completedTxns->sum('fee');

            $storedVolume = $opening?->total_volume !== null ? (float) $opening->total_volume : 0;
            $storedCommission = $opening?->total_commission !== null ? (float) $opening->total_commission : 0;
            $storedCount = $opening?->total_transactions !== null ? (int) $opening->total_transactions : 0;

            $useStored = $opening !== null && ($storedCount > 0 || $storedVolume > 0);

            return [
                'date' => $day->format('D, j M'),
                'opening_cash' => (float) ($opening?->cash_opening ?? 0),
                'opening_float' => (float) ($opening?->totalFloatOpening() ?? 0),
                'closing_cash' => $opening?->cash_closing !== null ? (float) $opening->cash_closing : null,
                'closing_float' => $opening?->totalFloatClosing() !== null ? (float) $opening->totalFloatClosing() : null,
                'deposits' => (float) (clone $txnQuery)->where('type', 'deposit')->sum('amount'),
                'withdrawals' => (float) (clone $txnQuery)->where('type', 'withdrawal')->sum('amount'),
                'volume' => $useStored ? $storedVolume : (float) $completedTxns->sum('amount'),
                'commission' => $useStored ? $storedCommission : (float) $completedTxns->sum('commission'),
                'count' => $useStored ? $storedCount : (int) $completedTxns->count(),
                'fees' => $fees,
                'net_revenue' => ($useStored ? $storedCommission : (float) $completedTxns->sum('commission')) - $fees,
            ];
        });

        $byNetwork = Network::withCount(['transactions as txn_count' => fn ($q) => $q->where('status', 'completed')])
            ->get()
            ->map(fn (Network $n) => [
                'id' => $n->id,
                'name' => $n->name,
                'color' => $n->color,
                'count' => (int) $n->txn_count,
                'volume' => (float) $n->transactions()->where('status', 'completed')->sum('amount'),
                'commission' => (float) $n->transactions()->where('status', 'completed')->sum('commission'),
            ]);

        $byAgent = [];

        $allTxns = Transaction::where('status', 'completed');
        if ($agentId !== null) {
            $allTxns->where('agent_id', $agentId);
        }

        $cards = [
            'totalVolume' => (float) (clone $allTxns)->sum('amount'),
            'totalCommission' => (float) (clone $allTxns)->sum('commission'),
            'totalFees' => (float) (clone $allTxns)->sum('fee'),
            'completed' => (int) (clone $allTxns)->count(),
            'reversed' => (int) Transaction::where('status', 'reversed')->when($agentId, fn ($q) => $q->where('agent_id', $agentId))->count(),
            'failed' => (int) Transaction::where('status', 'failed')->when($agentId, fn ($q) => $q->where('agent_id', $agentId))->count(),
        ];
        $cards['netRevenue'] = $cards['totalCommission'] - $cards['totalFees'];

        $todayOpening = null;
        if ($agentId !== null) {
            $todayOpening = DailyOpening::forAgentAndDate($agentId, today())->first();
        }

        $withdrawalSum = (float) (clone $allTxns)->where('type', 'withdrawal')->sum('amount');
        $payoutSum = (float) (clone $allTxns)->whereIn('type', ['withdrawal', 'send_money', 'bill_payment', 'airtime', 'data'])->sum('amount');
        $totalVolumeSum = (float) (clone $allTxns)->sum('amount');

        $cashFlow = [
            'cashIn' => $totalVolumeSum,
            'cashOut' => $withdrawalSum,
            'netFloatIn' => $totalVolumeSum - $payoutSum,
            'openingCash' => $todayOpening?->cash_opening ?? 0,
            'openingFloat' => $todayOpening?->totalFloatOpening() ?? 0,
        ];

        return view('reports.index', compact('report', 'daily', 'byNetwork', 'byAgent', 'cards', 'cashFlow', 'todayOpening'));
    }
}
