<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Tests\TestCase;

/**
 * Covers the four dashboard charts the store owner relies on: transaction
 * volume trend, average transaction value, transaction status and fees vs
 * commission.
 */
class DashboardChartsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The dashboard series covers 30 days ending today, so "today" is always
     * the last bucket.
     */
    private function series30(): array
    {
        $response = $this->actingAs($this->admin())->get(route('dashboard'));
        $response->assertOk();

        return $response->viewData('series30');
    }

    public function test_fee_and_commission_include_income_booked_on_the_float_ledger(): void
    {
        $agent = $this->cashPoint();

        // Customer transaction income.
        Transaction::factory()->create([
            'agent_id' => $agent->id,
            'type' => 'withdrawal',
            'status' => 'completed',
            'amount' => 100_000,
            'fee' => 500,
            'commission' => 2_000,
        ]);

        // Float ledger income: a top-up fee and a cash-to-float commission.
        FloatTransaction::factory()->topUp()->create([
            'agent_id' => $agent->id,
            'amount' => 5_000_000,
            'fee' => 1_500,
            'commission' => 0,
        ]);

        FloatTransaction::factory()->cashToFloat()->create([
            'agent_id' => $agent->id,
            'amount' => 300_000,
            'fee' => 0,
            'commission' => 4_000,
        ]);

        $series = $this->series30();
        $today = array_key_last($series['fees']);

        // Reading only the transactions table would have reported 500/2,000 and
        // lost both float income rows.
        $this->assertEqualsWithDelta(2_000.0, $series['fees'][$today], 0.01);
        $this->assertEqualsWithDelta(6_000.0, $series['commission'][$today], 0.01);
    }

    public function test_reversed_income_is_excluded_from_the_fee_and_commission_chart(): void
    {
        Transaction::factory()->create([
            'agent_id' => $this->cashPoint()->id,
            'status' => 'reversed',
            'fee' => 9_999,
            'commission' => 9_999,
        ]);

        $series = $this->series30();

        $this->assertSame(0.0, $series['fees'][array_key_last($series['fees'])]);
        $this->assertSame(0.0, $series['commission'][array_key_last($series['commission'])]);
    }

    public function test_average_transaction_value_is_null_on_days_without_activity(): void
    {
        Transaction::factory()->create([
            'agent_id' => $this->cashPoint()->id,
            'type' => 'deposit',
            'status' => 'completed',
            'amount' => 10_000,
        ]);
        Transaction::factory()->create([
            'agent_id' => $this->cashPoint()->id,
            'type' => 'deposit',
            'status' => 'completed',
            'amount' => 30_000,
        ]);

        $series = $this->series30();
        $avg = $series['avgValue'];

        $this->assertCount(30, $avg);
        // Today's average is 20,000 across the two deposits.
        $this->assertEqualsWithDelta(20_000.0, $avg[array_key_last($avg)], 0.01);

        // Idle days report "no data" (null) rather than a fake 0, which would
        // draw a false collapse to zero on the chart.
        $this->assertContains(null, $avg, 'Idle days must not report an average of 0.');
        $this->assertSame(count(array_filter($avg, fn ($v) => $v === null)), 29);
    }

    public function test_volume_and_average_exclude_internal_float_movements(): void
    {
        $agent = $this->cashPoint();

        Transaction::factory()->create([
            'agent_id' => $agent->id,
            'type' => 'deposit',
            'status' => 'completed',
            'amount' => 20_000,
        ]);

        // A large internal float movement: real money, but not customer volume.
        // Averaging it in would make the "average transaction value" useless.
        Transaction::factory()->create([
            'agent_id' => $agent->id,
            'type' => 'float_deposit',
            'status' => 'completed',
            'amount' => 10_000_000,
        ]);

        $series = $this->series30();
        $today = array_key_last($series['volume']);

        $this->assertEqualsWithDelta(20_000.0, $series['volume'][$today], 0.01);
        $this->assertSame(1, $series['counts'][$today]);
        $this->assertEqualsWithDelta(20_000.0, $series['avgValue'][$today], 0.01);
    }

    public function test_status_chart_buckets_every_status_per_day(): void
    {
        $agent = $this->cashPoint();

        foreach (['completed', 'completed', 'pending', 'failed', 'reversed'] as $status) {
            Transaction::factory()->create([
                'agent_id' => $agent->id,
                'type' => 'deposit',
                'status' => $status,
                'amount' => 5_000,
            ]);
        }

        $series = $this->series30();
        $today = array_key_last($series['statuses']['completed']);

        $this->assertSame(2, $series['statuses']['completed'][$today]);
        $this->assertSame(1, $series['statuses']['pending'][$today]);
        $this->assertSame(1, $series['statuses']['failed'][$today]);
        $this->assertSame(1, $series['statuses']['reversed'][$today]);
    }

    public function test_dashboard_renders_all_four_charts_with_their_summaries(): void
    {
        $user = $this->admin();

        Transaction::factory()->count(3)->create([
            'agent_id' => $this->cashPoint()->id,
            'network_id' => Network::factory(),
            'type' => 'deposit',
            'status' => 'completed',
            'amount' => 4_000,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Transaction volume trend')
            ->assertSee('Transaction status')
            ->assertSee('Average transaction value')
            ->assertSee('Fees vs commission')
            // Canvases the JS binds to.
            ->assertSee('id="volumeChart"', false)
            ->assertSee('id="statusChart"', false)
            ->assertSee('id="avgChart"', false)
            ->assertSee('id="feesCommChart"', false)
            // Period summary readouts.
            ->assertSee('id="volTotal"', false)
            ->assertSee('id="statusRate"', false)
            ->assertSee('id="avgMean"', false)
            ->assertSee('id="fcShare"', false);

        // The compact axis formatter the charts rely on.
        $response->assertSee('dashCompact', false);
    }

    /**
     * Guards the customer/internal split. If a new transaction type is added
     * without being classified, it would silently vanish from the volume and
     * average-value charts, so the lists are pinned here.
     */
    public function test_every_recordable_transaction_type_is_classified_for_the_charts(): void
    {
        $customer = (new ReflectionClass(DashboardController::class))->getConstant('CUSTOMER_TYPES');

        // Every type TransactionController::combos() lets an operator record.
        $recordable = [
            'deposit', 'withdrawal', 'send_money', 'bill_payment', 'airtime', 'data',
            'bank_to_wallet', 'wallet_to_bank', 'float_deposit', 'float_topup',
            'cash_to_float', 'commission_income',
        ];
        $internal = ['float_deposit', 'float_topup', 'cash_to_float', 'commission_income'];

        $this->assertSame($recordable, array_values(array_merge($customer, $internal)),
            'Every recordable transaction type must be either customer or internal.');
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }
}
