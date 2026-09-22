<?php

namespace App\Http\Controllers;

use App\Models\Network;
use App\Models\Transaction;
use App\Services\TransactionJournalService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinanceController extends Controller
{
    /**
     * Ranges accepted by the period filter, mapped to a start date (null = all time).
     */
    private const RANGES = [
        'today' => 'today',
        '7d' => '7d',
        'month' => 'month',
        'all' => 'all',
    ];

    private const TABS = ['pnl', 'network', 'type', 'settlement'];

    public function __invoke(Request $request): View|RedirectResponse|StreamedResponse
    {
        app(TransactionJournalService::class)->ensureSynced();

        if (cash_point() === null) {
            return redirect()->route('cash-point.index')->with('error', 'Set up the cash point first before viewing finance.');
        }

        $range = $request->string('range', 'month')->toString();

        if (! in_array($range, array_keys(self::RANGES), true)) {
            $range = 'month';
        }

        $tab = $request->string('tab', 'pnl')->toString();

        if (! in_array($tab, self::TABS, true)) {
            $tab = 'pnl';
        }

        $start = $this->startOf($range);
        $completed = fn (Builder $query): Builder => $query->where('status', 'completed')
            ->when($start !== null, fn (Builder $q): Builder => $q->where('created_at', '>=', $start));

        $cards = $this->cards($completed);
        $breakdown = $this->breakdown($completed, $range);
        $byNetwork = $this->byNetwork($completed);
        $byType = $this->byType($completed);
        $settlement = $this->settlement($completed);

        if ($request->boolean('export')) {
            return $this->export($tab, $range, $cards, $breakdown, $byNetwork, $byType, $settlement);
        }

        return view('finance.index', compact(
            'range',
            'tab',
            'start',
            'cards',
            'breakdown',
            'byNetwork',
            'byType',
            'settlement'
        ));
    }

    /**
     * @param  \Closure(Builder): Builder  $completed
     * @return array<string, float|int>
     */
    private function cards(\Closure $completed): array
    {
        $cards = [
            'deposits' => (float) $completed(Transaction::query())->where('type', 'deposit')->sum('amount'),
            'withdrawals' => (float) $completed(Transaction::query())->where('type', 'withdrawal')->sum('amount'),
            'volume' => (float) $completed(Transaction::query())->sum('amount'),
            'commission' => (float) $completed(Transaction::query())->sum('commission'),
            'fees' => (float) $completed(Transaction::query())->sum('fee'),
            'count' => (int) $completed(Transaction::query())->count(),
        ];

        $cards['net'] = (float) $cards['commission'] - (float) $cards['fees'];

        return $cards;
    }

    /**
     * @param  \Closure(Builder): Builder  $completed
     * @return array<int, array<string, mixed>>
     */
    private function breakdown(\Closure $completed, string $range): array
    {
        $breakdown = [];

        if ($range === 'all') {
            $monthly = $completed(Transaction::query())
                ->selectRaw('SUBSTRING(created_at, 1, 7) as month, COUNT(*) as count, SUM(amount) as amount, SUM(commission) as commission')
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            foreach ($monthly as $row) {
                $breakdown[] = [
                    'label' => Carbon::createFromFormat('!Y-m', (string) $row->month)->format('M Y'),
                    'count' => (int) $row->count,
                    'amount' => (float) $row->amount,
                    'commission' => (float) $row->commission,
                ];
            }

            return $breakdown;
        }

        $from = $this->startOf($range) ?? today();

        $rows = $completed(Transaction::query())
            ->selectRaw('DATE(created_at) as day, COUNT(*) as count, SUM(amount) as amount, SUM(commission) as commission')
            ->where('created_at', '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $frame = ($range === 'today')
            ? collect([today()])
            : collect(range(0, ($range === '7d' ? 6 : (int) today()->day - 1)))
                ->map(fn (int $d): Carbon => ($range === '7d')
                    ? today()->subDays(6 - $d)
                    : today()->startOfMonth()->addDays($d));

        foreach ($frame as $date) {
            $row = $rows->get($date->toDateString());

            $breakdown[] = [
                'label' => $range === 'today' ? 'Today' : $date->format('D, j M'),
                'count' => $row?->count ?? 0,
                'amount' => $row !== null ? (float) $row->amount : 0.0,
                'commission' => $row !== null ? (float) $row->commission : 0.0,
            ];
        }

        return $breakdown;
    }

    /**
     * @param  \Closure(Builder): Builder  $completed
     * @return array<int, array<string, mixed>>
     */
    private function byNetwork(\Closure $completed): array
    {
        $rows = $completed(Transaction::query())
            ->selectRaw('network_id, COUNT(*) as count, SUM(amount) as volume, SUM(commission) as commission')
            ->groupBy('network_id')
            ->get();

        $networks = Network::pluck('name', 'id');

        return $rows->map(function ($row) use ($networks) {
            return [
                'name' => $networks->get($row->network_id, 'Unknown'),
                'count' => (int) $row->count,
                'volume' => (float) $row->volume,
                'commission' => (float) $row->commission,
            ];
        })->sortByDesc('volume')->values()->all();
    }

    /**
     * @param  \Closure(Builder): Builder  $completed
     * @return array<int, array<string, mixed>>
     */
    private function byType(\Closure $completed): array
    {
        $rows = $completed(Transaction::query())
            ->selectRaw('type, COUNT(*) as count, SUM(amount) as volume, SUM(commission) as commission')
            ->groupBy('type')
            ->get();

        return $rows->map(function ($row) {
            return [
                'type' => (string) $row->type,
                'label' => txn_type_label((string) $row->type),
                'count' => (int) $row->count,
                'volume' => (float) $row->volume,
                'commission' => (float) $row->commission,
            ];
        })->sortByDesc('volume')->values()->all();
    }

    /**
     * @param  \Closure(Builder): Builder  $completed
     * @return array<string, mixed>
     */
    private function settlement(\Closure $completed): array
    {
        $agent = cash_point();

        $floats = $agent->balances()->with('network')->get();

        return [
            'cash' => (float) $agent->cash_balance,
            'float' => (float) $floats->sum('balance'),
            'total' => (float) $agent->cash_balance + (float) $floats->sum('balance'),
            'netFloatIn' => (float) $completed(Transaction::query())->where('type', 'deposit')->sum('amount')
                - (float) $completed(Transaction::query())->whereIn('type', ['withdrawal', 'send_money', 'bill_payment', 'airtime', 'data'])->sum('amount'),
            'count' => (int) $completed(Transaction::query())->count(),
            'perNetwork' => $floats->map(fn ($float) => [
                'name' => $float->network?->name ?? 'Unknown',
                'balance' => (float) $float->balance,
            ])->sortByDesc('balance')->values()->all(),
        ];
    }

    private function startOf(string $range): ?Carbon
    {
        return match ($range) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(6)->startOfDay(),
            'month' => now()->startOfMonth(),
            default => null,
        };
    }

    /**
     * CSV export for the currently selected tab.
     */
    protected function export(
        string $tab,
        string $range,
        array $cards,
        array $breakdown,
        array $byNetwork,
        array $byType,
        array $settlement
    ): StreamedResponse {
        $rows = match ($tab) {
            'network' => array_map(fn (array $row) => [$row['name'], $row['count'], $row['volume'], $row['commission']], $byNetwork),
            'type' => array_map(fn (array $row) => [$row['label'], $row['count'], $row['volume'], $row['commission']], $byType),
            'settlement' => array_merge(
                [[
                    'Cash on hand',
                    'Float in networks',
                    'Net float movement',
                    'Completed',
                ], [
                    $settlement['cash'],
                    $settlement['float'],
                    $settlement['netFloatIn'],
                    $settlement['count'],
                ]],
                array_map(fn (array $row) => [$row['name'], 'float', $row['balance'], ''], $settlement['perNetwork'])
            ),
            default => array_map(fn (array $row) => [$row['label'], $row['count'], $row['amount'], $row['commission']], $breakdown),
        };

        $filename = 'finance-'.$tab.'-'.$range.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');

            fputcsv($out, ['Label', 'Count', 'TZS Amount', 'TZS Commission']);

            foreach ($rows as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
