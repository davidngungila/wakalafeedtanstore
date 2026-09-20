<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashPointTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_point_helper_returns_null_when_none_is_configured(): void
    {
        $this->assertSame(0, Agent::count());

        $this->assertNull(cash_point());
    }

    public function test_cash_point_helper_returns_the_existing_agent(): void
    {
        $agent = $this->cashPoint();

        $this->assertSame($agent->id, cash_point()?->id);
    }

    public function test_dashboard_renders_with_setup_prompt_when_no_cash_point(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('cash point (wakala) is not set up');
    }

    public function test_admin_sees_the_setup_form_on_the_cash_point_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('cash-point.index'))
            ->assertOk()
            ->assertSee('Set up cash point')
            ->assertSee('Save cash point');
    }

    public function test_cashier_sees_a_setup_notice_instead_of_the_form(): void
    {
        $cashier = User::factory()->create(['role' => 'cashier']);

        $this->actingAs($cashier)
            ->get(route('cash-point.index'))
            ->assertOk()
            ->assertSee('Ask an administrator')
            ->assertDontSee('Save cash point');
    }

    public function test_admin_can_set_up_the_cash_point_via_the_update_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('cash-point.update'), [
                'code' => 'DMN-001',
                'name' => 'Kilimani Money Point',
                'owner_name' => 'Wakala Feed Tan Store',
                'phone' => '0712345678',
                'national_id' => '19840514-601210-00121-1',
                'region' => 'Kilimanjaro',
                'district' => 'Moshi',
                'ward' => 'Mfumuni',
                'street' => 'Bondeni Street',
                'agent_level' => 'platinum',
                'status' => 'active',
            ])
            ->assertRedirect(route('cash-point.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('agents', [
            'code' => 'DMN-001',
            'name' => 'Kilimani Money Point',
            'status' => 'active',
        ]);
        $this->assertSame(1, Agent::count());
    }

    public function test_admin_can_edit_an_existing_cash_point(): void
    {
        $this->cashPoint();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('cash-point.update'), [
                'code' => 'DMN-001',
                'name' => 'Kilimani Money Point (Renamed)',
                'phone' => '0712345678',
                'agent_level' => 'gold',
                'status' => 'active',
            ])
            ->assertRedirect(route('cash-point.index'));

        $this->assertDatabaseHas('agents', [
            'code' => 'DMN-001',
            'name' => 'Kilimani Money Point (Renamed)',
            'agent_level' => 'gold',
        ]);
        $this->assertSame(1, Agent::count());
    }

    public function test_update_route_rejects_blank_cash_point_submissions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('cash-point.index'))
            ->put(route('cash-point.update'), [
                'code' => '',
                'name' => '',
                'phone' => '',
                'agent_level' => '',
                'status' => '',
            ])
            ->assertSessionHasErrors(['code', 'name', 'phone', 'agent_level', 'status']);

        $this->assertSame(0, Agent::count());
    }
}
