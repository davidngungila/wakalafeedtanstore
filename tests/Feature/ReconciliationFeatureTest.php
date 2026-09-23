<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\DailyOpening;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'recon@moneyagent.local',
            'role' => 'supervisor',
            'is_active' => true,
        ], $overrides));
    }

    private function setupAgent(): Agent
    {
        $agent = $this->cashPoint();
        $agent->update(['cash_balance' => 250000]);

        $network = Network::factory()->create(['name' => 'M-Pesa', 'color' => '#00a000']);

        NetworkBalance::create([
            'agent_id' => $agent->id,
            'network_id' => $network->id,
            'opening_balance' => 75000,
            'balance' => 75000,
        ]);

        return $agent;
    }

    public function test_create_page_renders_the_reconciliation_run(): void
    {
        $this->setupAgent();

        $this->actingAs($this->user())
            ->get(route('reconciliation.create'))
            ->assertOk()
            ->assertSee('New Reconciliation')
            ->assertSee('Cash reconciliation')
            ->assertSee('Expected closing cash')
            ->assertSee('250,000')
            ->assertSee('M-Pesa Float')
            ->assertSee('Tie-out check');
    }

    public function test_store_creates_a_variance_record_when_cash_differs(): void
    {
        $this->setupAgent();

        $this->actingAs($this->user())
            ->from(route('reconciliation.create'))
            ->post(route('reconciliation.store'), [
                'reconciliation_date' => date('Y-m-d'),
                'counted_cash' => 240000,
                'notes' => 'Short by 10k',
            ])
            ->assertSessionHas('status', 'Reconciliation saved.');

        $this->assertDatabaseHas('reconciliations', [
            'expected_cash' => 250000,
            'counted_cash' => 240000,
            'cash_variance' => -10000,
            'status' => 'variance',
        ]);
    }

    public function test_store_marks_record_reconciled_and_updates_cash_till(): void
    {
        $agent = $this->setupAgent();

        $networkId = $agent->balances()->first()->network_id;

        $this->actingAs($this->user())
            ->post(route('reconciliation.store'), [
                'reconciliation_date' => date('Y-m-d'),
                'counted_cash' => 250000,
                'counted_floats' => [$networkId => 75000],
            ])
            ->assertSessionHas('status', 'Reconciliation saved.');

        $this->assertDatabaseHas('reconciliations', [
            'expected_cash' => 250000,
            'counted_cash' => 250000,
            'cash_variance' => 0,
            'float_variance' => 0,
            'tie_out' => 0,
            'status' => 'reconciled',
        ]);

        $this->assertSame(250000.0, (float) Agent::query()->first()->cash_balance);
        $this->assertSame(75000.0, (float) $agent->balances()->first()->balance);
    }

    public function test_store_uses_the_days_daily_opening_and_customer_activity(): void
    {
        $agent = $this->setupAgent();
        $user = $this->user();
        $networkId = $agent->balances()->first()->network_id;

        DailyOpening::create([
            'agent_id' => $agent->id,
            'user_id' => $user->id,
            'opening_date' => today(),
            'cash_opening' => 100000,
            'float_openings' => [$networkId => 50000],
        ]);

        Transaction::factory()->create([
            'agent_id' => $agent->id,
            'network_id' => $networkId,
            'type' => 'deposit',
            'amount' => 20000,
            'status' => 'completed',
        ]);

        Transaction::factory()->create([
            'agent_id' => $agent->id,
            'network_id' => $networkId,
            'type' => 'withdrawal',
            'amount' => 10000,
            'status' => 'completed',
        ]);

        // Types outside deposit/withdrawal are excluded from the run.
        Transaction::factory()->create([
            'agent_id' => $agent->id,
            'network_id' => $networkId,
            'type' => 'airtime',
            'amount' => 5000,
            'status' => 'completed',
        ]);

        $this->actingAs($user)
            ->from(route('reconciliation.create'))
            ->post(route('reconciliation.store'), [
                'reconciliation_date' => today()->toDateString(),
                'counted_cash' => 110000,
                'counted_floats' => [$networkId => 40000],
            ])
            ->assertSessionHas('status', 'Reconciliation saved.');

        $this->assertDatabaseHas('reconciliations', [
            'opening_cash' => 100000,
            'cash_deposits' => 20000,
            'cash_withdrawals' => 10000,
            'expected_cash' => 110000,
            'opening_float' => 50000,
            'total_float' => 40000,
            'float_variance' => 0,
            'tie_out' => 0,
            'status' => 'reconciled',
        ]);
    }

    public function test_store_reports_variance_when_counted_totals_deviate_from_opening(): void
    {
        $this->setupAgent();

        $this->actingAs($this->user())
            ->from(route('reconciliation.create'))
            ->post(route('reconciliation.store'), [
                'reconciliation_date' => date('Y-m-d'),
                'counted_cash' => 240000,
            ])
            ->assertSessionHas('status', 'Reconciliation saved.');

        // Opening total = 250,000 cash + 75,000 float = 325,000.
        // Counted closing = 240,000 cash + 75,000 float = 315,000 → tie-out 10,000.
        $this->assertDatabaseHas('reconciliations', [
            'expected_cash' => 250000,
            'cash_variance' => -10000,
            'tie_out' => 10000,
            'status' => 'variance',
        ]);
    }

    public function test_invalid_store_returns_validation_errors(): void
    {
        $this->setupAgent();

        $this->actingAs($this->user())
            ->post(route('reconciliation.store'), [
                'reconciliation_date' => '',
                'counted_cash' => -5,
            ])
            ->assertSessionHasErrors(['reconciliation_date', 'counted_cash']);

        $this->assertSame(0, Reconciliation::count());
    }
}
