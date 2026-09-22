<?php

namespace App\Http\Controllers;

use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $today = today();

        $cashPoint = cash_point();
        $cashAvailable = $cashPoint?->cash_balance;
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

        $series7 = $this->series(7);
        $series30 = $this->series(30);

        // Volume by network (all time)
        $networkTotals = Network::withCount(['transactions as completed_volume' => fn ($q) => $q->where('status', 'completed')->selectRaw('COALESCE(SUM(amount),0)')])
            ->orderByDesc('completed_volume')
            ->get();

        $networkDonutMax = $networkTotals->map(fn ($n) => $n->completed_volume)->sum() ?: 1;

        // Commission by network (all time) — ranked
        $commissionByNetwork = Network::withCount(['transactions as completed_commission' => fn ($q) => $q->where('status', 'completed')->selectRaw('COALESCE(SUM(commission),0)')])
            ->orderByDesc('completed_commission')
            ->get();

        $commissionRankMax = $commissionByNetwork->map(fn ($n) => $n->completed_commission)->max() ?: 1;

        $recentTransactions = Transaction::with(['network', 'agent'])
            ->latest()
            ->limit(5)
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
        )->sortByDesc('time')->take(5)->values();

        $networkBalances = Network::with('balances')->get()->map(fn (Network $n) => [
            'name' => $n->name,
            'color' => $n->color,
            'balance' => (float) $n->balances->sum('balance'),
        ]);

        return view('dashboard.index', compact(
            'cashPoint',
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
            'series7',
            'series30',
            'networkTotals',
            'networkDonutMax',
            'commissionByNetwork',
            'commissionRankMax',
            'recentTransactions',
            'recentActivity',
            'networkBalances'
        ));
    }

    /**
     * Build daily chart data (labels, deposit/withdrawal volume, total value,
     * float snapshots and status counts) for the last N days.
     *
     * @return array{
     *     labels: array<int, string>,
     *     deposits: array<int, float>,
     *     withdrawals: array<int, float>,
     *     volume: array<int, float>,
     *     float: array<int, float>,
     *     statuses: array<string, array<int, int>>,
     *     chartMax: float
     * }
     */
    private function series(int $days): array
    {
        $start = today()->subDays($days - 1)->startOfDay();
        $end = today()->endOfDay();

        $rows = Transaction::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, type, status, SUM(amount) as total, COUNT(*) as cnt')
            ->groupBy('day', 'type', 'status')
            ->get();

        $labels = [];
        $deposits = $withdrawals = $volume = [];
        $statuses = ['completed' => [], 'pending' => [], 'failed' => [], 'reversed' => []];

        // Chronological buckets: index 0 = oldest day, last index = today.
        for ($i = 0; $i < $days; $i++) {
            $day = today()->subDays($days - 1 - $i);
            $labels[$i] = $day->format('d M');
            $deposits[$i] = 0.0;
            $withdrawals[$i] = 0.0;
            $volume[$i] = 0.0;

            foreach (array_keys($statuses) as $status) {
                $statuses[$status][$i] = 0;
            }
        }

        // Re-key rows into the per-day buckets.
        foreach ($rows as $row) {
            $day = Carbon::parse($row->day)->startOfDay();
            $index = $day->diffInDays($start);

            if (! isset($deposits[$index])) {
                continue;
            }

            $amount = (float) $row->total;
            $count = (int) $row->cnt;
            $isCompleted = $row->status === 'completed';

            if ($isCompleted && $row->type === 'deposit') {
                $deposits[$index] += $amount;
            } elseif ($isCompleted && $row->type === 'withdrawal') {
                $withdrawals[$index] += $amount;
            }

            if ($isCompleted) {
                $volume[$index] += $amount;
            }

            if (array_key_exists($row->status, $statuses)) {
                $statuses[$row->status][$index] += $count;
            }
        }

        $float = $this->floatSeries($start, $labels);
        $chartMax = max(1.0, max(array_merge($deposits, $withdrawals)));

        return [
            'labels' => array_values($labels),
            'deposits' => array_values($deposits),
            'withdrawals' => array_values($withdrawals),
            'volume' => array_values($volume),
            'float' => $float,
            'statuses' => array_map(fn (array $s) => array_values($s), $statuses),
            'chartMax' => $chartMax,
        ];
    }

    /**
     * Daily closing float, reconstructed from the last recorded
     * running_float_balance on transaction snapshots (carried forward), seeded
     * with the newest snapshot before the window and ending at the live total.
     *
     * @param  Carbon  $start  Start of window.
     * @param  array<int, string>  $labels  Day keys aligned to the series.
     * @return array<int, float>
     */
    private function floatSeries(Carbon $start, array $labels): array
    {
        $seed = Transaction::whereNotNull('running_float_balance')
            ->where('created_at', '<', $start)
            ->orderByDesc('created_at')
            ->value('running_float_balance');

        $current = $seed !== null ? (float) $seed : (float) NetworkBalance::sum('balance');

        $snapshots = Transaction::whereNotNull('running_float_balance')
            ->where('created_at', '>=', $start)
            ->orderBy('created_at')
            ->get(['created_at', 'running_float_balance']);

        $count = count($labels);
        $values = [];

        // Chronological: index 0 = oldest day, last index = today.
        for ($i = 0; $i < $count; $i++) {
            $dayStart = today()->subDays($count - 1 - $i)->startOfDay();

            $latest = null;
            foreach ($snapshots as $snapshot) {
                if ($snapshot->created_at->between($dayStart, $dayStart->copy()->endOfDay())) {
                    $latest = (float) $snapshot->running_float_balance;
                }
            }

            if ($latest !== null) {
                $current = $latest;
            }

            $values[$i] = $current;
        }

        // Anchor the final (today) point to the live total.
        $values[$count - 1] = (float) NetworkBalance::sum('balance');

        return $values;
    }
}
