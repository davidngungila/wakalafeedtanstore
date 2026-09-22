<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Reconciliation;
use App\Models\ReconciliationCorrection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconciliationCorrectionFeatureTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'corr@moneyagent.local',
            'role' => 'supervisor',
            'is_active' => true,
        ], $overrides));
    }

    private function setupReconciliation(): array
    {
        $agent = Agent::factory()->create([
            'code' => 'CASHPT',
            'name' => 'Cash Point',
            'status' => 'active',
            'cash_balance' => 500000,
        ]);

        $network = Network::factory()->create(['name' => 'M-Pesa', 'color' => '#00a000']);

        NetworkBalance::create([
            'agent_id' => $agent->id,
            'network_id' => $network->id,
            'opening_balance' => 100000,
            'balance' => 150000,
        ]);

        $reconciliation = Reconciliation::create([
            'agent_id' => $agent->id,
            'reconciliation_date' => date('Y-m-d'),
            'opening_cash' => 500000,
            'expected_cash' => 500000,
            'counted_cash' => 520000,
            'cash_variance' => 20000,
            'total_float' => 150000,
            'float_variance' => 0,
            'network_balances' => [
                [
                    'network' => 'M-Pesa',
                    'system' => 150000,
                    'counted' => 150000,
                ],
            ],
            'status' => 'variance',
        ]);

        return [$reconciliation, $agent, $network];
    }

    public function test_show_page_renders_full_session_details(): void
    {
        [$reconciliation] = $this->setupReconciliation();

        $this->actingAs($this->user())
            ->get(route('reconciliation.show', $reconciliation))
            ->assertOk()
            ->assertSee('Reconciliation #'.$reconciliation->code)
            ->assertSee('Channels breakdown')
            ->assertSee('Cash in Till')
            ->assertSee('M-Pesa Float')
            ->assertSee('Add correction')
            ->assertSee('520,000');
    }

    public function test_add_correction_page_renders_correction_form(): void
    {
        [$reconciliation] = $this->setupReconciliation();

        $this->actingAs($this->user())
            ->get(route('reconciliation.corrections.create', $reconciliation))
            ->assertOk()
            ->assertSee('Add correction')
            ->assertSee('Fix differences with a reference')
            ->assertSee('Applies to')
            ->assertSee('Cash in Till')
            ->assertSee('Money received in error')
            ->assertSee('Reference')
            ->assertSee('Amount (TZS)')
            ->assertSee('Record correction');
    }

    public function test_recording_cash_correction_marks_session_resolved_when_fully_settled(): void
    {
        [$reconciliation] = $this->setupReconciliation();

        $this->actingAs($this->user())
            ->from(route('reconciliation.show', $reconciliation))
            ->post(route('reconciliation.corrections.store', $reconciliation), [
                'scope' => 'cash',
                'network_id' => '',
                'type' => ReconciliationCorrection::TYPE_ERROR_FUNDS,
                'reference' => 'Customer sent 20,000 by mistake, not refunded',
                'amount' => 20000,
            ])
            ->assertSessionHas('status', 'Correction recorded.');

        $this->assertDatabaseHas('reconciliation_corrections', [
            'reconciliation_id' => $reconciliation->id,
            'scope' => 'cash',
            'type' => ReconciliationCorrection::TYPE_ERROR_FUNDS,
            'reference' => 'Customer sent 20,000 by mistake, not refunded',
            'amount' => 20000,
        ]);

        $this->assertSame('resolved', $reconciliation->fresh()->status);
    }

    public function test_partial_correction_leaves_session_in_variance(): void
    {
        [$reconciliation] = $this->setupReconciliation();

        $this->actingAs($this->user())
            ->post(route('reconciliation.corrections.store', $reconciliation), [
                'scope' => 'cash',
                'type' => ReconciliationCorrection::TYPE_CUSTOMER_OVERPAID,
                'reference' => 'Overpaid by 5,000',
                'amount' => 5000,
            ]);

        $this->assertSame('variance', $reconciliation->fresh()->status);
    }

    public function test_float_correction_settles_that_network_channel(): void
    {
        [$reconciliation, , $network] = $this->setupReconciliation();

        $reconciliation->update([
            'float_variance' => 10000,
            'network_balances' => [
                [
                    'network' => 'M-Pesa',
                    'system' => 150000,
                    'counted' => 160000,
                ],
            ],
        ]);

        $user = $this->user();

        $this->actingAs($user)
            ->post(route('reconciliation.corrections.store', $reconciliation), [
                'scope' => 'float',
                'network_id' => $network->id,
                'type' => ReconciliationCorrection::TYPE_FLOAT_TOPUP,
                'reference' => 'Top-up received 10,000',
                'amount' => 10000,
            ]);

        // Cash channel (20,000 variance) remains unsettled — session still in variance.
        $this->assertSame('variance', $reconciliation->fresh()->status);

        $this->actingAs($user)
            ->post(route('reconciliation.corrections.store', $reconciliation), [
                'scope' => 'cash',
                'type' => ReconciliationCorrection::TYPE_ERROR_FUNDS,
                'reference' => 'Error funds kept 20,000',
                'amount' => 20000,
            ]);

        $this->assertSame('resolved', $reconciliation->fresh()->status);
    }

    public function test_deleting_correction_returns_session_to_variance(): void
    {
        [$reconciliation] = $this->setupReconciliation();

        $user = $this->user();

        $this->actingAs($user)
            ->post(route('reconciliation.corrections.store', $reconciliation), [
                'scope' => 'cash',
                'type' => ReconciliationCorrection::TYPE_ERROR_FUNDS,
                'reference' => 'Kept error funds',
                'amount' => 20000,
            ]);

        $this->assertSame('resolved', $reconciliation->fresh()->status);

        $correction = $reconciliation->corrections()->first();

        $this->actingAs($user)
            ->from(route('reconciliation.show', $reconciliation))
            ->delete(route('reconciliation.corrections.destroy', [$reconciliation, $correction]))
            ->assertSessionHas('status', 'Correction removed.');

        $this->assertDatabaseMissing('reconciliation_corrections', ['id' => $correction->id]);
        $this->assertSame('variance', $reconciliation->fresh()->status);
    }

    public function test_invalid_correction_returns_validation_errors(): void
    {
        [$reconciliation] = $this->setupReconciliation();

        $this->actingAs($this->user())
            ->post(route('reconciliation.corrections.store', $reconciliation), [
                'scope' => 'cash',
                'type' => ReconciliationCorrection::TYPE_ERROR_FUNDS,
                'reference' => '',
                'amount' => -5,
            ])
            ->assertSessionHasErrors(['reference', 'amount']);

        $this->assertSame(0, ReconciliationCorrection::count());
    }

    public function test_fully_reconciled_session_keeps_status_reconciled(): void
    {
        [$reconciliation] = $this->setupReconciliation();

        $reconciliation->update([
            'counted_cash' => 500000,
            'cash_variance' => 0,
            'status' => 'reconciled',
        ]);

        $this->actingAs($this->user())
            ->post(route('reconciliation.corrections.store', $reconciliation), [
                'scope' => 'cash',
                'type' => ReconciliationCorrection::TYPE_OTHER,
                'reference' => 'Nothing to fix',
                'amount' => 1,
            ]);

        $this->assertSame('reconciled', $reconciliation->fresh()->status);
    }

    public function test_correction_types_include_kept_error_funds(): void
    {
        $types = ReconciliationCorrection::types();

        $this->assertArrayHasKey(ReconciliationCorrection::TYPE_ERROR_FUNDS, $types);
        $this->assertStringContainsString('not refunded', $types[ReconciliationCorrection::TYPE_ERROR_FUNDS]);
    }
}
