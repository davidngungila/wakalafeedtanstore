<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Network;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceModuleTest extends TestCase
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

    private function cashier(): User
    {
        return User::where('email', 'cashier@moneyagent.local')->firstOrFail();
    }

    private function makeDevice(string $status = 'pending'): Device
    {
        return Device::create([
            'name' => 'Redmi Note 12',
            'agent_id' => cash_point()->id,
            'network_id' => Network::firstOrFail()->id,
            'api_token_hash' => hash('sha256', 'dv_test'),
            'status' => $status,
        ]);
    }

    public function test_devices_index_is_visible_to_supervisors_and_admins(): void
    {
        $this->actingAs($this->admin())->get(route('devices.index'))->assertOk();
    }

    public function test_devices_index_is_denied_to_cashiers(): void
    {
        $this->actingAs($this->cashier())->get(route('devices.index'))->assertForbidden();
    }

    public function test_admin_can_register_a_device_and_it_starts_pending(): void
    {
        $network = Network::firstOrFail();

        $response = $this->actingAs($this->admin())
            ->post(route('devices.store'), [
                'name' => 'Samsung A15',
                'network_id' => $network->id,
                'phone_number' => '0712345678',
                'sim_number' => '89410123456789012345',
                'branch' => 'Moshi',
            ]);

        $response->assertRedirect(route('devices.index'));

        $this->assertDatabaseHas('devices', [
            'name' => 'Samsung A15',
            'network_id' => $network->id,
            'phone_number' => '0712345678',
            'status' => 'pending',
        ]);
    }

    public function test_cashiers_cannot_register_devices(): void
    {
        $this->actingAs($this->cashier())
            ->post(route('devices.store'), ['name' => 'X', 'network_id' => Network::firstOrFail()->id])
            ->assertForbidden();
    }

    public function test_admin_can_approve_a_pending_device(): void
    {
        $device = $this->makeDevice();

        $this->actingAs($this->admin())
            ->post(route('devices.approve', $device))
            ->assertRedirect();

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'active',
        ]);
        $this->assertNotNull($device->fresh()->activated_at);
    }

    public function test_blocked_or_revoked_devices_cannot_be_reactivated(): void
    {
        $device = $this->makeDevice('revoked');

        $this->actingAs($this->admin())
            ->post(route('devices.approve', $device))
            ->assertStatus(422);

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'revoked',
        ]);
    }

    public function test_admin_can_revoke_a_device_and_invalidate_its_token(): void
    {
        $device = $this->makeDevice('active');

        $this->actingAs($this->admin())
            ->post(route('devices.revoke', $device))
            ->assertRedirect();

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'revoked',
            'api_token_hash' => null,
        ]);
    }

    public function test_device_detail_page_loads(): void
    {
        $device = $this->makeDevice('active');

        $this->actingAs($this->admin())
            ->get(route('devices.show', $device))
            ->assertOk()
            ->assertSee($device->name);
    }
}
