<?php

namespace Tests\Feature;

use App\Models\DailyOpening;
use App\Models\FloatTransaction;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FloatDaysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@moneyagent.local')->firstOrFail();
    }

    private function setupDay(): array
    {
        $agent = $this->cashPoint();
        $agent->update(['cash_balance' => 500_000]);

        $network = Network::where('code', 'HALOPESA')->firstOrFail();
        $balance = NetworkBalance::updateOrCreate(
            ['agent_id' => $agent->id, 'network_id' => $network->id],
            ['opening_balance' => 100_000, 'balance' => 100_000],
        );
        $opening = DailyOpening::create([
            'agent_id' => $agent->id,
            'user_id' => $this->admin()->id,
            'opening_date' => today(),
            'cash_opening' => 500_000,
            'float_openings' => [$network->id => 100_000],
        ]);

        return [$agent, $network, $balance, $opening];
    }

    public function test_days_page_links_to_cash_to_float_for_each_day(): void
    {
        $this->setupDay();

        $this->actingAs($this->admin())
            ->get(route('float.days'))
            ->assertOk()
            ->assertSee('Cash → Float')
            ->assertSee('Transfer cash to float for this day');
    }

    public function test_cash_to_float_form_is_preselected_for_the_selected_day(): void
    {
        [$agent, $network] = $this->setupDay();
        Transaction::factory()->create([
            'agent_id' => $agent->id,
            'network_id' => $network->id,
            'type' => 'deposit',
            'amount' => 100_000,
            'status' => 'completed',
        ]);

        $this->actingAs($this->admin())
            ->get(route('float.create', [
                'date' => encrypt(today()->toDateString()),
                'type' => 'cash_to_float',
            ]))
            ->assertOk()
            ->assertSee('Cash to Float')
            ->assertSee('Transfer this transaction', false)
            ->assertSee('Net float added (TZS)')
            ->assertSee('Commission / top-up fee (TZS)');
    }

    public function test_referenced_cash_to_float_requires_a_transaction(): void
    {
        [$agent, $network] = $this->setupDay();

        $this->actingAs($this->admin())
            ->from(route('float.create', ['date' => encrypt(today()->toDateString())]))
            ->post(route('float.store'), [
                'network_id' => $network->id,
                'type' => 'float_topup',
                'amount' => 60_000,
                'commission' => 0,
                'float_date' => today()->toDateString(),
                'reference_cash_to_float' => 1,
            ])
            ->assertSessionHasErrors('transaction_id');

        $this->assertSame(0, FloatTransaction::count());
    }

    public function test_completed_transaction_can_reference_a_cash_to_float_transfer(): void
    {
        [$agent, $network, $balance] = $this->setupDay();
        $transaction = Transaction::factory()->create([
            'agent_id' => $agent->id,
            'network_id' => $network->id,
            'type' => 'deposit',
            'amount' => 100_000,
            'commission' => 0,
            'status' => 'completed',
            'reference' => 'TXN-REF-CASH-0001',
            'provider_reference' => 'SR-REF-CASH-0001',
        ]);

        $this->actingAs($this->admin())
            ->from(route('float.create', ['date' => encrypt(today()->toDateString())]))
            ->post(route('float.store'), [
                'network_id' => $network->id,
                'type' => 'float_topup',
                'amount' => 60_000,
                'commission' => 1_000,
                'float_date' => today()->toDateString(),
                'transaction_id' => $transaction->id,
                'reference_cash_to_float' => 1,
            ])
            ->assertRedirect();

        $floatTransaction = FloatTransaction::firstOrFail();
        $transaction->refresh();
        $balance->refresh();
        $agent->refresh();

        $this->assertSame('cash_to_float', $floatTransaction->type);
        $this->assertSame(60_000.0, (float) $floatTransaction->amount);
        $this->assertSame(1_000.0, (float) $floatTransaction->commission);
        $this->assertStringContainsString('TXN-REF-CASH-0001', (string) $floatTransaction->notes);
        $this->assertStringContainsString('cash_to_float', (string) $transaction->notes);
        $this->assertSame(159_000.0, (float) $balance->balance);
        $this->assertSame(440_000.0, (float) $agent->cash_balance);
    }

    public function test_cash_to_float_transfers_the_entered_amount_after_commission(): void
    {
        [$agent, $network, $balance, $opening] = $this->setupDay();

        $this->actingAs($this->admin())
            ->from(route('float.days'))
            ->post(route('float.store'), [
                'network_id' => $network->id,
                'type' => 'cash_to_float',
                'amount' => 250_000,
                'commission' => 5_000,
                'float_date' => today()->toDateString(),
                'notes' => 'Partial transfer',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $floatTransaction = FloatTransaction::firstOrFail();
        $balance->refresh();
        $agent->refresh();

        $this->assertSame('cash_to_float', $floatTransaction->type);
        $this->assertSame(250_000.0, (float) $floatTransaction->amount);
        $this->assertSame(5_000.0, (float) $floatTransaction->commission);
        $this->assertSame($opening->id, $floatTransaction->daily_opening_id);
        $this->assertSame(345_000.0, (float) $balance->balance);
        $this->assertSame(250_000.0, (float) $agent->cash_balance);
    }

    public function test_cash_to_float_rejects_more_than_the_cash_at_till(): void
    {
        [$agent, $network, $balance] = $this->setupDay();

        $this->actingAs($this->admin())
            ->from(route('float.days'))
            ->post(route('float.store'), [
                'network_id' => $network->id,
                'type' => 'cash_to_float',
                'amount' => 600_000,
                'commission' => 0,
                'float_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        $balance->refresh();
        $agent->refresh();

        $this->assertSame(0, FloatTransaction::count());
        $this->assertSame(100_000.0, (float) $balance->balance);
        $this->assertSame(500_000.0, (float) $agent->cash_balance);
    }

    public function test_cash_to_float_rejects_an_amount_equal_to_commission(): void
    {
        [$agent, $network, $balance] = $this->setupDay();

        $this->actingAs($this->admin())
            ->from(route('float.days'))
            ->post(route('float.store'), [
                'network_id' => $network->id,
                'type' => 'cash_to_float',
                'amount' => 5_000,
                'commission' => 5_000,
                'float_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');

        $balance->refresh();
        $agent->refresh();

        $this->assertSame(0, FloatTransaction::count());
        $this->assertSame(100_000.0, (float) $balance->balance);
        $this->assertSame(500_000.0, (float) $agent->cash_balance);
    }

    public function test_cash_to_float_is_included_in_cash_and_float_reconciliation(): void
    {
        [$agent, $network, $balance] = $this->setupDay();

        $this->actingAs($this->admin())
            ->post(route('float.store'), [
                'network_id' => $network->id,
                'type' => 'cash_to_float',
                'amount' => 250_000,
                'commission' => 5_000,
                'float_date' => today()->toDateString(),
            ])
            ->assertRedirect();

        $this->actingAs($this->admin())
            ->post(route('reconciliation.store'), [
                'reconciliation_date' => today()->toDateString(),
                'counted_cash' => 250_000,
                'counted_floats' => [$network->id => 345_000],
            ])
            ->assertSessionHas('status', 'Reconciliation saved.');

        $this->assertDatabaseHas('reconciliations', [
            'expected_cash' => 250_000,
            'total_float' => 345_000,
            'cash_variance' => 0,
            'float_variance' => 0,
            'tie_out' => 0,
            'status' => 'reconciled',
        ]);

        $balance->refresh();
        $agent->refresh();

        $this->assertSame(345_000.0, (float) $balance->balance);
        $this->assertSame(250_000.0, (float) $agent->cash_balance);
    }

    public function test_past_day_transfer_is_recorded_without_changing_live_balances(): void
    {
        [$agent, $network, $balance] = $this->setupDay();
        $pastDate = today()->subDay();

        DailyOpening::create([
            'agent_id' => $agent->id,
            'user_id' => $this->admin()->id,
            'opening_date' => $pastDate,
            'cash_opening' => 500_000,
            'float_openings' => [$network->id => 100_000],
        ]);

        $this->actingAs($this->admin())
            ->from(route('float.days'))
            ->post(route('float.store'), [
                'network_id' => $network->id,
                'type' => 'cash_to_float',
                'amount' => 50_000,
                'commission' => 500,
                'float_date' => $pastDate->toDateString(),
            ])
            ->assertRedirect();

        $floatTransaction = FloatTransaction::firstOrFail();
        $balance->refresh();
        $agent->refresh();

        $this->assertSame($pastDate->toDateString(), $floatTransaction->created_at->toDateString());
        $this->assertSame(100_000.0, (float) $balance->balance);
        $this->assertSame(500_000.0, (float) $agent->cash_balance);
    }
}
