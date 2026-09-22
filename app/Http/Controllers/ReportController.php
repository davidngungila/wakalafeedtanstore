<?php

namespace App\Http\Controllers;

use App\Models\DailyOpening;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Services\TransactionJournalService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        app(TransactionJournalService::class)->ensureSynced();

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

        // ── Chart suite (same 16-panel set as dashboard) ────────────────────────
        $today = today();
        $cashPoint = cash_point();
        $cashAvailable = $cashPoint?->cash_balance;
        $floatAvailable = NetworkBalance::sum('balance');

        $series7 = $this->series(7);
        $series30 = $this->series(30);

        $networkTotals = Network::withCount(['transactions as completed_volume' => fn ($q) => $q->where('status', 'completed')->selectRaw('COALESCE(SUM(amount),0)')])
            ->orderByDesc('completed_volume')->get();
        $networkDonutMax = $networkTotals->map(fn ($n) => $n->completed_volume)->sum() ?: 1;

        $commissionByNetwork = Network::withCount(['transactions as completed_commission' => fn ($q) => $q->where('status', 'completed')->selectRaw('COALESCE(SUM(commission),0)')])
            ->orderByDesc('completed_commission')->get();
        $commissionRankMax = $commissionByNetwork->map(fn ($n) => $n->completed_commission)->max() ?: 1;

        $countByNetwork = Network::withCount(['transactions as completed_count' => fn ($q) => $q->where('status', 'completed')])
            ->orderByDesc('completed_count')->get();
        $countRankMax = $countByNetwork->map(fn ($n) => $n->completed_count)->max() ?: 1;

        $hourlyCounts = array_fill(0, 24, 0);
        Transaction::where('status', 'completed')->where('created_at', '>=', today()->subDays(30))->pluck('created_at')
            ->each(fn (Carbon $c) => $hourlyCounts[(int) $c->format('G')]++);

        $reconVariance = Reconciliation::orderByDesc('reconciliation_date')->limit(14)
            ->get(['reconciliation_date', 'cash_variance'])->reverse()->values();

        $cashFlowWaterfall = $this->cashFlowWaterfall($today, (float) ($cashAvailable ?? 0));
        $networkMatrix = $this->networkMatrix();
        $valueDistribution = $this->valueDistribution();

        $networkBalances = Network::with('balances')->get()->map(fn (Network $n) => [
            'name' => $n->name,
            'color' => $n->color,
            'balance' => (float) $n->balances->sum('balance'),
        ]);

        $todayDeposits = Transaction::whereDate('created_at', $today)->where('type', 'deposit')->where('status', 'completed')->sum('amount');
        $todayWithdrawals = Transaction::whereDate('created_at', $today)->where('type', 'withdrawal')->where('status', 'completed')->sum('amount');
        $monthCommission = Transaction::where('created_at', '>=', today()->startOfMonth())->where('status', 'completed')->sum('commission');
        $monthFees = Transaction::where('created_at', '>=', today()->startOfMonth())->where('status', 'completed')->sum('fee');
        $pendingCount = Transaction::where('status', 'pending')->count();
        $failedCount = Transaction::where('status', 'failed')->count();

        return view('reports.index', compact(
            'report',
            'daily',
            'byNetwork',
            'byAgent',
            'cards',
            'cashFlow',
            'todayOpening',
            'cashPoint',
            'cashAvailable',
            'floatAvailable',
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
            'cashFlowWaterfall',
            'networkMatrix',
            'valueDistribution',
            'networkBalances',
            'todayDeposits',
            'todayWithdrawals',
            'monthCommission',
            'monthFees',
            'pendingCount',
            'failedCount'
        ));
    }

    private function series(int $days): array
    {
        $start = today()->subDays($days - 1)->startOfDay();
        $end = today()->endOfDay();

        $rows = Transaction::whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as day, type, status, SUM(amount) as total, COUNT(*) as cnt, SUM(fee) as fees, SUM(commission) as commission')
            ->groupBy('day', 'type', 'status')->get();

        $labels = [];
        $deposits = $withdrawals = $volume = $counts = $fees = $commission = [];
        $statuses = ['completed' => [], 'pending' => [], 'failed' => [], 'reversed' => []];

        for ($i = 0; $i < $days; $i++) {
            $day = today()->subDays($days - 1 - $i);
            $labels[$i] = $day->format('d M');
            $deposits[$i] = $withdrawals[$i] = $volume[$i] = $counts[$i] = $fees[$i] = $commission[$i] = 0.0;
            foreach (array_keys($statuses) as $status) {
                $statuses[$status][$i] = 0;
            }
        }

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
                $counts[$index] += $count;
                $fees[$index] += (float) $row->fees;
                $commission[$index] += (float) $row->commission;
            }
            if (array_key_exists($row->status, $statuses)) {
                $statuses[$row->status][$index] += $count;
            }
        }

        $float = $this->balanceSeries($start, $labels, 'running_float_balance', NetworkBalance::sum('balance'));
        $cash = $this->balanceSeries($start, $labels, 'running_cash_balance', (float) (cash_point()?->cash_balance ?? 0));
        $avgValue = array_map(fn (float $v, int $c) => $c > 0 ? $v / $c : 0.0, $volume, $counts);
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

    private function balanceSeries(Carbon $start, array $labels, string $column, float $liveTotal): array
    {
        $seed = Transaction::whereNotNull($column)->where('created_at', '<', $start)->orderByDesc('created_at')->value($column);
        $current = $seed !== null ? (float) $seed : $liveTotal;
        $snapshots = Transaction::whereNotNull($column)->where('created_at', '>=', $start)->orderBy('created_at')->get(['created_at', $column]);
        $count = count($labels);
        $values = [];
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
        $values[$count - 1] = $liveTotal;

        return $values;
    }

    private function cashFlowWaterfall(Carbon $today, float $cashAvailable): array
    {
        $open = (float) DailyOpening::where('opening_date', $today->toDateString())->value('cash_opening');
        if ($open <= 0) {
            $open = (float) (Transaction::where('created_at', '<', $today->startOfDay())->whereNotNull('running_cash_balance')->orderByDesc('created_at')->value('running_cash_balance') ?? $cashAvailable);
        }
        $inTypes = ['deposit', 'float_deposit', 'float_topup', 'bank_to_wallet', 'airtime'];
        $outTypes = ['withdrawal', 'wallet_to_bank'];
        $todayRow = Transaction::whereDate('created_at', $today)->where('status', 'completed')
            ->selectRaw('COALESCE(SUM(CASE WHEN type IN (?, ?, ?, ?, ?) THEN amount ELSE 0 END),0) as inflows', $inTypes)
            ->selectRaw('COALESCE(SUM(CASE WHEN type IN (?, ?) THEN amount ELSE 0 END),0) as outflows', $outTypes)->first();
        $inflows = (float) ($todayRow->inflows ?? 0);
        $outflows = (float) ($todayRow->outflows ?? 0);
        $labels = ['Opening cash', 'Inflows (+)', 'Outflows (−)', 'Closing cash'];
        $bases = [0.0, $open, $open + $inflows, 0.0];
        $tops = [$open, $open + $inflows, $open + $inflows - $outflows, $cashAvailable];
        $colors = ['#7A5C42', '#5E6E3F', '#B33A3A', '#C2592B'];

        return compact('labels', 'bases', 'tops', 'colors');
    }

    private function networkMatrix(): array
    {
        $blocks = ['00–03', '04–07', '08–11', '12–15', '16–19', '20–23'];
        $networks = Network::orderBy('name')->get(['id', 'name', 'color']);
        $rows = $networks->mapWithKeys(fn (Network $n) => [$n->id => ['name' => $n->name, 'color' => $n->color ?: '#A98968', 'cells' => array_fill(0, 6, 0)]]);
        Transaction::where('status', 'completed')->where('created_at', '>=', today()->subDays(7))->where('created_at', '<', today()->addDay())
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

        return ['blocks' => $blocks, 'rows' => $rows->values()->all()];
    }

    private function valueDistribution(): array
    {
        $edges = [0, 5000, 10000, 25000, 50000, 100000, 500000];
        $labels = ['<5k', '5–10k', '10–25k', '25–50k', '50–100k', '100–500k', '500k+'];
        $values = array_fill(0, count($edges), 0);
        Transaction::where('status', 'completed')->pluck('amount')->each(function ($amount) use ($edges, &$values) {
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
