<?php

namespace App\Http\Controllers;

use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Transaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();

        $cashAvailable = cash_point()->cash_balance;
        $floatAvailable = NetworkBalance::sum('balance');

        $todayDeposits = Transaction::whereDate('created_at', $today)->where('type', 'deposit')->where('status', 'completed')->sum('amount');
        $todayWithdrawals = Transaction::whereDate('created_at', $today)->where('type', 'withdrawal')->where('status', 'completed')->sum('amount');
        $todayCommission = Transaction::whereDate('created_at', $today)->where('status', 'completed')->sum('commission');
        $todayFees = Transaction::whereDate('created_at', $today)->where('status', 'completed')->sum('fee');

        $monthCommission = Transaction::where('created_at', '>=', today()->startOfMonth())->where('status', 'completed')->sum('commission');
        $monthFees = Transaction::where('created_at', '>=', today()->startOfMonth())->where('status', 'completed')->sum('fee');

        $totalCommission = Transaction::where('status', 'completed')->sum('commission');
        $totalFees = Transaction::where('status', 'completed')->sum('fee');

        $pendingCount = Transaction::where('status', 'pending')->count();
        $failedCount = Transaction::where('status', 'failed')->count();

        // 7-day volume bars
        $chart = collect(range(6, 0))->map(function (int $daysAgo) {
            $day = today()->subDays($daysAgo);

            return [
                'label' => $day->format('D'),
                'deposits' => (float) Transaction::whereDate('created_at', $day)->where('type', 'deposit')->where('status', 'completed')->sum('amount'),
                'withdrawals' => (float) Transaction::whereDate('created_at', $day)->where('type', 'withdrawal')->where('status', 'completed')->sum('amount'),
            ];
        });

        $chartMax = max(1, $chart->flatMap(fn ($d) => [$d['deposits'], $d['withdrawals']])->max());

        // Volume by network
        $networkTotals = Network::withCount(['transactions as completed_volume' => fn ($q) => $q->where('status', 'completed')->selectRaw('COALESCE(SUM(amount),0)')])
            ->orderByDesc('completed_volume')
            ->get();

        $networkDonutMax = $networkTotals->map(fn ($n) => $n->completed_volume)->sum() ?: 1;

        $recentTransactions = Transaction::with(['network', 'agent'])
            ->latest()
            ->limit(10)
            ->get();

        $recentActivity = $recentTransactions->map(fn (Transaction $t) => [
            'kind' => 'transaction',
            'icon' => $t->type,
            'text' => txn_type_label($t->type).' · '.$t->customer_name,
            'meta' => money($t->amount).' · '.($t->network->name ?? '—'),
            'time' => $t->created_at,
            'status' => $t->status,
        ])->concat(
            FloatTransaction::with('network')->latest()->limit(5)->get()->map(fn (FloatTransaction $f) => [
                'kind' => 'float',
                'icon' => $f->type,
                'text' => txn_type_label($f->type),
                'meta' => money($f->amount).' · '.($f->network->name ?? '—'),
                'time' => $f->created_at,
                'status' => $f->status,
            ])
        )->sortByDesc('time')->take(10)->values();

        $networkBalances = Network::with('balances')->get()->map(fn (Network $n) => [
            'name' => $n->name,
            'color' => $n->color,
            'balance' => (float) $n->balances->sum('balance'),
        ]);

        return view('dashboard.index', compact(
            'cashAvailable',
            'floatAvailable',
            'todayDeposits',
            'todayWithdrawals',
            'todayCommission',
            'todayFees',
            'monthCommission',
            'monthFees',
            'totalCommission',
            'totalFees',
            'pendingCount',
            'failedCount',
            'chart',
            'chartMax',
            'networkTotals',
            'networkDonutMax',
            'recentTransactions',
            'recentActivity',
            'networkBalances'
        ));
    }
}
