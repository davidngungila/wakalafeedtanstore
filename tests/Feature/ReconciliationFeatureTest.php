<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Reconciliation;
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
            'opening_balance' => 50000,
            'balance' => 75000,
        ]);

        return $agent;
    }

    public function test_create_page_renders_expected_balances(): void
    {
        $this->setupAgent();

        $this->actingAs($this->user())
            ->get(route('reconciliation.create'))
            ->assertOk()
            ->assertSee('New Reconciliation')
            ->assertSee('Expected cash')
            ->assertSee('250,000')
            ->assertSee('M-Pesa Float');
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
            'status' => 'reconciled',
        ]);

        $this->assertSame(250000.0, (float) Agent::query()->first()->cash_balance);
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
