<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\CommissionRate;
use App\Models\DailyOpening;
use App\Models\NetworkBalance;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    public function __construct(private readonly TransactionJournalService $journals = new TransactionJournalService) {}

    /**
     * Create a transaction and apply the float/cash adjustments.
     *
     * @param  array{network_id: int, type: string, customer_name?: string|null, customer_phone: string, amount: float}  $data
     * @param  float|null  $commissionOverride  When provided (e.g. Preview Commission from SMS), use it instead of the rate table.
     */
    public function process(
        array $data,
        ?Agent $agent = null,
        ?int $performedBy = null,
        string $notes = 'Processed on counter',
        ?string $providerReference = null,
        ?float $commissionOverride = null,
    ): Transaction {
        $agent ??= cash_point();

        if ($agent === null) {
            throw new \LogicException('The cash point has not been set up. Configure it in Settings → Cash Point first.');
        }

        $rate = $this->commissionFor($agent, $data['network_id'], $data['type'], (float) $data['amount']);

        $computedCommission = round((float) $data['amount'] * $rate / 100, 2);
        $commission = $commissionOverride !== null && $commissionOverride >= 0 ? round($commissionOverride, 2) : $computedCommission;
        $fee = $this->feeFor($data['type'], (float) $data['amount']);

        $balance = NetworkBalance::firstOrCreate(
            ['agent_id' => $agent->id, 'network_id' => $data['network_id']],
            ['opening_balance' => 0, 'balance' => 0]
        );

        $dailyOpening = DailyOpening::forAgentAndDate($agent->id, today())
            ->open()
            ->first();

        $reference = 'TXN-'.now()->format('ymd').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        return DB::transaction(function () use ($agent, $balance, $data, $commission, $fee, $reference, $performedBy, $providerReference, $notes, $dailyOpening) {
            $adjustFloat = function (float $delta) use ($balance) {
                $balance->balance += $delta;
                $balance->save();
            };

            // Agent perspective: customer deposit / float deposit -> agent receives cash (+cash) and gives float (-float)
            // customer withdrawal / float withdrawal -> agent gives cash (-cash) and receives float (+float)
            // bank_to_wallet / float top-up -> money moves FROM the bank INTO the wallet: float +amount, cash unchanged
            match ($data['type']) {
                'deposit', 'float_deposit', 'float_topup' => $adjustFloat(-(float) $data['amount']),
                'withdrawal', 'bank_to_wallet' => $adjustFloat((float) $data['amount']),
                default => $adjustFloat(-(float) $data['amount']),
            };

            $cashDelta = 0;

            if (in_array($data['type'], ['deposit', 'withdrawal', 'float_deposit', 'float_topup', 'wallet_to_bank', 'airtime'], true)) {
                $direction = in_array($data['type'], ['deposit', 'float_deposit', 'float_topup', 'airtime'], true) ? 1 : -1;
                // wallet_to_bank is opposite of bank_to_wallet
                if ($data['type'] === 'wallet_to_bank') {
                    $direction = -1;
                }
                $cashDelta = $direction * (float) $data['amount'];
                $agent->cash_balance = ((float) $agent->cash_balance) + $cashDelta;
                $agent->save();
            }

            $payload = [
                'daily_opening_id' => $dailyOpening?->id,
                'reference' => $reference,
                'agent_id' => $agent->id,
                'network_id' => $data['network_id'],
                'type' => $data['type'],
                'customer_name' => $data['customer_name'] ?? null,
                'customer_phone' => $data['customer_phone'],
                'amount' => $data['amount'],
                'fee' => $fee,
                'commission' => $commission,
                'status' => 'completed',
                'provider_reference' => $providerReference ?? 'SR'.random_int(10000000, 99999999),
                'performed_by' => $performedBy,
                'notes' => $notes,
                'running_cash_balance' => $agent->cash_balance,
                'running_float_balance' => $agent->totalFloat(),
            ];
            if (\Illuminate\Support\Facades\Schema::hasColumn('transactions', 'running_network_balance')) {
                $payload['running_network_balance'] = $balance->balance;
            }
            $txn = Transaction::create($payload);

            if ($dailyOpening !== null) {
                $dailyOpening->addTransactionVolume((float) $txn->amount, (float) $txn->commission);
            }

            $txn->load('network');
            $this->journals->postForTransaction($txn);

            return $txn;
        });
    }

    private function commissionFor(Agent $agent, int $networkId, string $type, float $amount): float
    {
        $rate = CommissionRate::where('network_id', $networkId)
            ->where('agent_level', $agent->agent_level)
            ->where('transaction_type', $type)
            ->where('is_active', true)
            ->first();

        return $rate?->rate ?? 0;
    }

    private function feeFor(string $type, float $amount): float
    {
        return match ($type) {
            'withdrawal' => min(5000, max(200, round($amount * 0.002, 2))),
            'bill_payment' => round($amount * 0.003, 2),
            'bank_to_wallet', 'wallet_to_bank' => round($amount * 0.001, 2),
            default => 0,
        };
    }
}
