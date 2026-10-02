<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\DailyOpening;
use App\Models\Network;
use App\Models\NetworkBalance;
use App\Models\Reconciliation;
use App\Models\Transaction;
use App\Models\User;
use App\Support\Shift;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Changing the shift on a report re-bases it onto a different window, so the
 * expected cash and float have to be rebuilt and only an administrator may do
 * it.
 */
class ReconciliationShiftChangeTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::firstOrCreate(
            ['email' => $role.'@shiftchange.local'],
            [
                'name' => ucfirst($role).' User',
                'role' => $role,
                'is_active' => true,
                'password' => 'password',
                'email_verified_at' => now(),
                'two_factor_enabled' => true,
            ]
        );
    }

    private function agent(): Agent
    {
        $agent = $this->cashPoint();

        Network::factory()->create(['name' => 'M-Pesa', 'color' => '#00a000']);

        NetworkBalance::create([
            'agent_id' => $agent->id,
            'network_id' => Network::first()->id,
            'opening_balance' => 100_000,
            'balance' => 100_000,
        ]);

        $network = Network::first();

        DailyOpening::create([
            'agent_id' => $agent->id,
            'user_id' => $this->user('admin')->id,
            'opening_date' => today()->toDateString(),
            'shift' => Shift::FULL,
            'cash_opening' => 200_000,
            'float_openings' => [$network->id => 100_000],
        ]);

        return $agent;
    }

    private function depositAt(Carbon $at, float $amount): Transaction
    {
        return Transaction::factory()->create([
            'agent_id' => $this->cashPoint()->id,
            'network_id' => Network::first()->id,
            'type' => 'deposit',
            'status' => 'completed',
            'amount' => $amount,
            'fee' => 0,
            'commission' => 0,
            'created_at' => $at,
        ]);
    }

    private function nightRecord(): Reconciliation
    {
        $agent = $this->agent();
        $date = today()->subDays(2);

        // Only a night transaction, so a night report has expected cash of 0 +
        // opening while a catch-up from the same date also sees the morning one.
        $this->depositAt($date->copy()->setTime(21, 0), 50_000);
        $this->depositAt($date->copy()->setTime(9, 0), 30_000);

        return Reconciliation::create([
            'agent_id' => $agent->id,
            'reconciliation_date' => $date->toDateString(),
            'shift' => Shift::NIGHT,
            'opening_cash' => 0,
            'cash_deposits' => 50_000,
            'cash_withdrawals' => 0,
            'expected_cash' => 50_000,
            'opening_float' => 100_000,
            'counted_cash' => 50_000,
            'cash_variance' => 0,
            'total_float' => 100_000,
            'float_variance' => 0,
            'tie_out' => 0,
            'network_balances' => [[
                'network_id' => Network::first()->id,
                'network' => 'M-Pesa',
                'opening' => 100_000,
                'deposits' => 50_000,
                'withdrawals' => 0,
                'float_topups' => 0,
                'bank_ins' => 0,
                'expected' => 100_000,
                'system' => 100_000,
                'counted' => 100_000,
                'variance' => 0,
            ]],
            'status' => 'reconciled',
        ]);
    }

    public function test_only_an_admin_sees_the_shift_selector(): void
    {
        $record = $this->nightRecord();

        $this->actingAs($this->user('admin'))
            ->get(route('reconciliation.edit', $record))
            ->assertOk()
            ->assertSee('Shift covered by this report')
            ->assertSee('name="shift"', false);

        // A supervisor can still recorrect counts, but not re-base the shift.
        $this->actingAs($this->user('supervisor'))
            ->get(route('reconciliation.edit', $record))
            ->assertOk()
            ->assertDontSee('Shift covered by this report');
    }

    public function test_a_supervisor_cannot_change_the_shift(): void
    {
        $record = $this->nightRecord();
        $originalShift = $record->shift;

        $this->actingAs($this->user('supervisor'))
            ->put(route('reconciliation.update', $record), [
                'counted_cash' => 50_000,
                'counted_floats' => ['M-Pesa' => 100_000],
                'shift' => Shift::CATCHUP,
            ])
            ->assertRedirect();

        $this->assertSame($originalShift, $record->fresh()->shift, 'A supervisor must not be able to re-base the shift.');
    }

    public function test_an_admin_changing_the_shift_rebuilds_the_expected_figures(): void
    {
        $record = $this->nightRecord();

        $this->assertSame(50_000.0, (float) $record->expected_cash, 'Night report sees only the night deposit.');

        $this->actingAs($this->user('admin'))
            ->put(route('reconciliation.update', $record), [
                'counted_cash' => 80_000,
                // Float: opening 100,000 less the two deposits (30,000 + 50,000)
                // leaves 20,000 expected once the catch-up window is applied.
                'counted_floats' => ['M-Pesa' => 20_000],
                'shift' => Shift::CATCHUP,
            ])
            ->assertRedirect();

        $record->refresh();

        $this->assertSame(Shift::CATCHUP, $record->shift);

        // The catch-up window from that date at 08:00 to now includes both the
        // morning and the night deposit, so the expected cash must be rebuilt.
        $this->assertEqualsWithDelta(80_000.0, (float) $record->expected_cash, 0.01);
        $this->assertEqualsWithDelta(80_000.0, (float) $record->cash_deposits, 0.01);
        $this->assertEqualsWithDelta(20_000.0, (float) $record->total_float, 0.01);

        // Counted figures were carried across, so the variance is now zero.
        $this->assertEqualsWithDelta(0.0, (float) $record->cash_variance, 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $record->float_variance, 0.01);
        $this->assertSame('reconciled', $record->status);
    }

    public function test_a_rebasable_report_shows_a_variance_until_it_is_recounted(): void
    {
        $record = $this->nightRecord();

        $this->actingAs($this->user('admin'))
            ->put(route('reconciliation.update', $record), [
                'counted_cash' => 50_000,
                'counted_floats' => ['M-Pesa' => 20_000],
                'shift' => Shift::CATCHUP,
            ])
            ->assertRedirect();

        $record->refresh();

        // Expected is now 80,000 but only 50,000 was counted: a real shortfall
        // the supervisor must see rather than have silently absorbed.
        $this->assertEqualsWithDelta(-30_000.0, (float) $record->cash_variance, 0.01);
        $this->assertSame('variance', $record->status);
    }

    public function test_an_approved_report_cannot_be_re_based(): void
    {
        $record = $this->nightRecord();
        $record->update([
            'approved_by' => $this->user('supervisor')->id,
            'approved_at' => now(),
        ]);

        $this->actingAs($this->user('admin'))
            ->get(route('reconciliation.edit', $record))
            ->assertOk()
            ->assertDontSee('Shift covered by this report');

        $this->actingAs($this->user('admin'))
            ->put(route('reconciliation.update', $record), [
                'counted_cash' => 80_000,
                'counted_floats' => ['M-Pesa' => 130_000],
                'shift' => Shift::CATCHUP,
            ])
            ->assertRedirect();

        $this->assertSame(Shift::NIGHT, $record->fresh()->shift);
    }

    public function test_the_shift_change_is_recorded_in_the_audit_trail(): void
    {
        $record = $this->nightRecord();

        $this->actingAs($this->user('admin'))
            ->put(route('reconciliation.update', $record), [
                'counted_cash' => 80_000,
                'counted_floats' => ['M-Pesa' => 130_000],
                'shift' => Shift::CATCHUP,
            ])
            ->assertRedirect();

        $audit = AuditLog::where('entity_type', 'Reconciliation')
            ->where('entity_id', $record->id)
            ->latest()
            ->first();

        $this->assertNotNull($audit, 'A shift change must leave an audit entry.');
        $this->assertSame(Shift::NIGHT, $audit->details['shift_from']);
        $this->assertSame(Shift::CATCHUP, $audit->details['shift_to']);
        $this->assertTrue($audit->details['shift_changed']);
    }
}
