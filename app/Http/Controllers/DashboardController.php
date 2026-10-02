<?php

namespace App\Http\Controllers;

use App\Models\DailyOpening;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Services\TransactionJournalService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Transaction types that represent money moved for a customer.
     *
     * Everything else recorded on the transactions table (float_deposit,
     * float_topup, cash_to_float, commission_income) is an internal float or
     * cash movement. Those are real rows but they are not customer activity, so
     * they are kept out of the volume and average-value charts — otherwise a
     * single 10,000,000 float top-up would swamp the scale and make the
     * "average transaction value" meaningless.
     */
    private const CUSTOMER_TYPES = [
        'deposit',
        'withdrawal',
        'send_money',
        'bill_payment',
        'airtime',
        'data',
        'bank_to_wallet',
        'wallet_to_bank',
    ];

    public function __invoke(): View
    {
        app(TransactionJournalService::class)->ensureSynced();

        $today = today();

        $cashPoint = cash_point();
        $cashAvailable = $cashPoint?->cash_balance;
        $floatAvailable = NetworkBalance::sum('balance');

        $todayDeposits = Transaction::whereDate('created_at', $today)->whereIn('type', ['deposit', 'airtime', 'send_money', 'float_deposit'])->where('status', 'completed')->sum('amount');
        $todayWithdrawals = Transaction::whereDate('created_at', $today)->where('type', 'withdrawal')->where('status', 'completed')->sum('amount');
        // Fees and commission are earned from two ledgers: the transactions table and
        // the float table (which books a fee on every top-up). Summing only
        // transactions left the float income out of every total below.
        $todayCommission = $this->income('commission', $today->copy()->startOfDay(), $today->copy()->endOfDay());
        $todayFees = $this->income('fee', $today->copy()->startOfDay(), $today->copy()->endOfDay());

        $monthCommission = $this->income('commission', today()->startOfMonth());
        $monthFees = $this->income('fee', today()->startOfMonth());

        $totalCommission = $this->income('commission');
        $totalFees = $this->income('fee');

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

        // Transaction count by network (all time)
        $countByNetwork = Network::withCount(['transactions as completed_count' => fn ($q) => $q->where('status', 'completed')])
            ->orderByDesc('completed_count')
            ->get();

        $countRankMax = $countByNetwork->map(fn ($n) => $n->completed_count)->max() ?: 1;

        // Hourly transaction activity (last 30 days, completed)
        $hourlyCounts = array_fill(0, 24, 0);
        Transaction::where('status', 'completed')
            ->where('created_at', '>=', today()->subDays(30))
            ->pluck('created_at')
            ->each(fn (Carbon $c) => $hourlyCounts[(int) $c->format('G')]++);

        // Reconciliation variance — last 14 reconciled days
        $reconVariance = Reconciliation::orderByDesc('reconciliation_date')
            ->limit(14)
            ->get(['reconciliation_date', 'cash_variance'])
            ->reverse()
            ->values();

        // Daily net cash flow waterfall (today)
        $cashFlow = $this->cashFlowWaterfall($today, (float) $cashAvailable);

        // Network performance matrix — completed tx last 7 days grouped by network × 4h block
        $networkMatrix = $this->networkMatrix();

        // Transaction value distribution (all-time completed buckets)
        $valueDistribution = $this->valueDistribution();

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
            'countByNetwork',
            'countRankMax',
            'hourlyCounts',
            'reconVariance',
            'cashFlow',
            'networkMatrix',
            'valueDistribution',
            'recentTransactions',
            'recentActivity',
            'networkBalances'
        ));
    }

    /**
     * Completed fee or commission income across both income ledgers.
     *
     * Fees and commission are earned from two separate tables: `transactions`
     * and `float_transactions` (the latter books a fee on every top-up and a
     * commission on every cash-to-float conversion). Reading only the
     * transactions table left all float income out of the dashboard totals and
     * out of the "Fees vs commission" chart.
     *
     * @param  'fee'|'commission'  $column
     */
    private function income(string $column, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $sum = fn (string $model): float => (float) $model::query()
            ->where('status', 'completed')
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->sum($column);

        return $sum(Transaction::class) + $sum(FloatTransaction::class);
    }

    /**
     * Build daily chart data for the last N days.
     *
     * `volume` and `counts` cover customer transactions only (see
     * self::CUSTOMER_TYPES) so the volume trend and the average-value chart
     * describe the same, meaningful population. `fees` and `commission` cover
     * every source of income: both the transactions ledger and the float
     * ledger, which records its own fee/commission on float top-ups.
     *
     * `avgValue` is null on days without customer activity — an average over
     * zero transactions is undefined, and rendering it as 0 would draw a false
     * collapse to zero on the chart.
     *
     * Rows are bucketed through an exact `Y-m-d` lookup rather than a day
     * offset. `Carbon::diffInDays()` is signed (today minus the window start
     * comes back negative), so using its result directly as an array index
     * produced a negative key, `isset()` failed, and every row was silently
     * discarded — which is why these charts rendered flat at zero.
     *
     * @return array{
     *     labels: array<int, string>,
     *     deposits: array<int, float>,
     *     withdrawals: array<int, float>,
     *     volume: array<int, float>,
     *     counts: array<int, int>,
     *     fees: array<int, float>,
     *     commission: array<int, float>,
     *     avgValue: array<int, float|null>,
     *     float: array<int, float>,
     *     cash: array<int, float>,
     *     statuses: array<string, array<int, int>>,
     *     chartMax: float
     * }
     */
    private function series(int $days): array
    {
        $start = today()->subDays($days - 1)->startOfDay();
        $end = today()->endOfDay();

        $rows = Transaction::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, type, status, SUM(amount) as total, COUNT(*) as cnt, SUM(fee) as fees, SUM(commission) as commission')
            ->groupBy('day', 'type', 'status')
            ->get();

        // Float movements carry their own fee (a network top-up charge) and
        // commission (e.g. cash_to_float). Reading only the transactions table
        // silently dropped all of it from the income charts.
        $floatRows = FloatTransaction::whereBetween('created_at', [$start, $end])
            ->where('status', 'completed')
            ->selectRaw('DATE(created_at) as day, COALESCE(SUM(fee),0) as fees, COALESCE(SUM(commission),0) as commission')
            ->groupBy('day')
            ->get();

        $labels = [];
        $deposits = $withdrawals = $volume = $counts = $fees = $commission = [];
        $statuses = ['completed' => [], 'pending' => [], 'failed' => [], 'reversed' => []];

        // Chronological buckets: index 0 = oldest day, last index = today.
        // $bucket maps a 'Y-m-d' day key straight to its series index.
        $bucket = [];
        for ($i = 0; $i < $days; $i++) {
            $day = today()->subDays($days - 1 - $i);
            $bucket[$day->toDateString()] = $i;
            $labels[$i] = $day->format('d M');
            $deposits[$i] = 0.0;
            $withdrawals[$i] = 0.0;
            $volume[$i] = 0.0;
            $counts[$i] = 0;
            $fees[$i] = 0.0;
            $commission[$i] = 0.0;

            foreach (array_keys($statuses) as $status) {
                $statuses[$status][$i] = 0;
            }
        }

        $indexOf = fn (string $day): ?int => $bucket[Carbon::parse($day)->startOfDay()->toDateString()] ?? null;

        // Re-key rows into the per-day buckets.
        foreach ($rows as $row) {
            $index = $indexOf($row->day);

            if ($index === null) {
                continue;
            }

            $amount = (float) $row->total;
            $count = (int) $row->cnt;
            $isCompleted = $row->status === 'completed';
            $isCustomer = in_array($row->type, self::CUSTOMER_TYPES, true);

            if ($isCompleted && in_array($row->type, ['deposit', 'airtime', 'send_money', 'float_deposit'], true)) {
                $deposits[$index] += $amount;
            } elseif ($isCompleted && $row->type === 'withdrawal') {
                $withdrawals[$index] += $amount;
            }

            if ($isCompleted) {
                // Income is earned on every completed row, customer or not.
                $fees[$index] += (float) $row->fees;
                $commission[$index] += (float) $row->commission;

                // Volume and counts describe customer activity only.
                if ($isCustomer) {
                    $volume[$index] += $amount;
                    $counts[$index] += $count;
                }
            }

            if (array_key_exists($row->status, $statuses)) {
                $statuses[$row->status][$index] += $count;
            }
        }

        foreach ($floatRows as $row) {
            $index = $indexOf($row->day);

            if ($index === null) {
                continue;
            }

            $fees[$index] += (float) $row->fees;
            $commission[$index] += (float) $row->commission;
        }

        $float = $this->balanceSeries($start, $labels, 'running_float_balance', NetworkBalance::sum('balance'));
        $cash = $this->balanceSeries($start, $labels, 'running_cash_balance', (float) (cash_point()?->cash_balance ?? 0));
        $avgValue = array_map(fn (float $v, int $c): ?float => $c > 0 ? round($v / $c, 2) : null, $volume, $counts);
        $chartMax = max(1.0, max(array_merge($deposits, $withdrawals)));

        return [
            'labels' => array_values($labels),
            'deposits' => array_values($deposits),
            'withdrawals' => array_values($withdrawals),
            'volume' => array_values($volume),
            'counts' => array_values($counts),
            'fees' => array_values($fees),
            'commission' => array_values($commission),
            'avgValue' => array_values($avgValue),
            'float' => $float,
            'cash' => $cash,
            'statuses' => array_map(fn (array $s) => array_values($s), $statuses),
            'chartMax' => $chartMax,
        ];
    }

    /**
     * Daily closing balance (cash or float), reconstructed from the last
     * recorded running_*_balance snapshots (carried forward), seeded with the
     * newest snapshot before the window and ending at the live total.
     *
     * @param  Carbon  $start  Start of window.
     * @param  array<int, string>  $labels  Day keys aligned to the series.
     * @return array<int, float>
     */
    private function balanceSeries(Carbon $start, array $labels, string $column, float $liveTotal): array
    {
        $seed = Transaction::whereNotNull($column)
            ->where('created_at', '<', $start)
            ->orderByDesc('created_at')
            ->value($column);

        $current = $seed !== null ? (float) $seed : $liveTotal;

        $snapshots = Transaction::whereNotNull($column)
            ->where('created_at', '>=', $start)
            ->orderBy('created_at')
            ->get(['created_at', $column]);

        $count = count($labels);
        $values = [];

        // Chronological: index 0 = oldest day, last index = today.
        for ($i = 0; $i < $count; $i++) {
            $dayStart = today()->subDays($count - 1 - $i)->startOfDay();

            $latest = null;
            foreach ($snapshots as $snapshot) {
                if ($snapshot->created_at->between($dayStart, $dayStart->copy()->endOfDay())) {
                    $latest = (float) $snapshot->{$column};
                }
            }

            if ($latest !== null) {
                $current = $latest;
            }

            $values[$i] = $current;
        }

        // Anchor the final (today) point to the live total.
        $values[$count - 1] = $liveTotal;

        return $values;
    }

    /**
     * Today's cash-flow waterfall: opening cash → inflows → outflows → closing.
     *
     * @return array{
     *     labels: array<int, string>,
     *     bases: array<int, float>,
     *     tops: array<int, float>,
     *     colors: array<int, string>
     * }
     */
    private function cashFlowWaterfall(Carbon $today, float $cashAvailable): array
    {
        $open = (float) DailyOpening::where('opening_date', $today->toDateString())->value('cash_opening');

        if ($open <= 0) {
            $open = (float) (Transaction::where('created_at', '<', $today->startOfDay())
                ->whereNotNull('running_cash_balance')
                ->orderByDesc('created_at')
                ->value('running_cash_balance') ?? $cashAvailable);
        }

        $inTypes = ['deposit', 'float_deposit', 'float_topup', 'bank_to_wallet', 'airtime', 'send_money'];
        $outTypes = ['withdrawal', 'wallet_to_bank', 'cash_to_float'];

        $todayRow = Transaction::whereDate('created_at', $today)
            ->where('status', 'completed')
            ->selectRaw('COALESCE(SUM(CASE WHEN type IN (?, ?, ?, ?, ?, ?) THEN amount ELSE 0 END),0) as inflows', $inTypes)
            ->selectRaw('COALESCE(SUM(CASE WHEN type IN (?, ?, ?) THEN amount ELSE 0 END),0) as outflows', $outTypes)
            ->first();

        $inflows = (float) ($todayRow->inflows ?? 0);
        $outflows = (float) ($todayRow->outflows ?? 0);

        $labels = ['Opening cash', 'Inflows (+)', 'Outflows (−)', 'Closing cash'];
        $bases = [0.0, $open, $open + $inflows, 0.0];
        $tops = [$open, $open + $inflows, $open + $inflows - $outflows, $cashAvailable];
        $colors = ['#7A5C42', '#5E6E3F', '#B33A3A', '#C2592B'];

        return compact('labels', 'bases', 'tops', 'colors');
    }

    /**
     * Completed transaction counts per network across four-hour blocks for
     * the last 7 days, for the performance heatmap.
     *
     * @return array{blocks: array<int, string>, rows: array<int, array{name: string, color: string, cells: array<int, int>}>}
     */
    private function networkMatrix(): array
    {
        $blocks = ['00–03', '04–07', '08–11', '12–15', '16–19', '20–23'];

        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);
        $rows = $networks->mapWithKeys(fn (Network $n) => [$n->id => [
            'name' => $n->name,
            'color' => $n->color ?: '#A98968',
            'cells' => array_fill(0, 6, 0),
        ]]);

        Transaction::where('status', 'completed')
            ->where('created_at', '>=', today()->subDays(7))
            ->where('created_at', '<', today()->addDay())
            ->get(['network_id', 'created_at'])
            ->each(function ($row) use ($rows) {
                $networkId = $row->network_id;
                if (! $rows->has($networkId)) {
                    return;
                }

                $block = (int) floor(Carbon::parse($row->created_at)->format('G') / 4);
                $block = max(0, min(5, $block));
                $entry = $rows->get($networkId);
                $entry['cells'][$block] += 1;
                $rows->put($networkId, $entry);
            });

        return [
            'blocks' => $blocks,
            'rows' => $rows->values()->all(),
        ];
    }

    /**
     * All-time completed transaction amount buckets for the histogram.
     *
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function valueDistribution(): array
    {
        $edges = [0, 5000, 10000, 25000, 50000, 100000, 500000];
        $labels = ['<5k', '5–10k', '10–25k', '25–50k', '50–100k', '100–500k', '500k+'];
        $values = array_fill(0, count($edges), 0);

        Transaction::where('status', 'completed')
            ->pluck('amount')
            ->each(function ($amount) use ($edges, &$values) {
                $amount = (float) $amount;
                $index = count($edges) - 1;
                foreach ($edges as $i => $edge) {
                    if ($amount < $edge) {
                        $index = $i - 1;
                        break;
                    }
                }
                $values[max(0, $index)] += 1;
            });

        return compact('labels', 'values');
    }
}
