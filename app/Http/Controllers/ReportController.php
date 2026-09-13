<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $report = $request->input('report', 'daily');

        $daily = collect(range(6, 0))->map(function (int $daysAgo) {
            $day = today()->subDays($daysAgo);

            return [
                'date' => $day->format('D, j M'),
                'deposits' => (float) Transaction::whereDate('created_at', $day)->where('type', 'deposit')->where('status', 'completed')->sum('amount'),
                'withdrawals' => (float) Transaction::whereDate('created_at', $day)->where('type', 'withdrawal')->where('status', 'completed')->sum('amount'),
                'volume' => (float) Transaction::whereDate('created_at', $day)->where('status', 'completed')->sum('amount'),
                'commission' => (float) Transaction::whereDate('created_at', $day)->where('status', 'completed')->sum('commission'),
                'count' => (int) Transaction::whereDate('created_at', $day)->where('status', 'completed')->count(),
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

        $cards = [
            'totalVolume' => (float) Transaction::where('status', 'completed')->sum('amount'),
            'totalCommission' => (float) Transaction::where('status', 'completed')->sum('commission'),
            'totalFees' => (float) Transaction::where('status', 'completed')->sum('fee'),
            'completed' => (int) Transaction::where('status', 'completed')->count(),
            'reversed' => (int) Transaction::where('status', 'reversed')->count(),
            'failed' => (int) Transaction::where('status', 'failed')->count(),
        ];
        $cards['netRevenue'] = $cards['totalCommission'] - $cards['totalFees'];

        $cashFlow = [
            'cashIn' => $cards['totalVolume'],
            'cashOut' => (float) Transaction::where('status', 'completed')->where('type', 'withdrawal')->sum('amount'),
            'netFloatIn' => $cards['totalVolume'] - (float) Transaction::where('status', 'completed')->whereIn('type', ['withdrawal', 'send_money', 'bill_payment', 'airtime', 'data'])->sum('amount'),
        ];

        return view('reports.index', compact('report', 'daily', 'byNetwork', 'byAgent', 'cards', 'cashFlow'));
    }
}
