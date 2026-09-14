<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Network;
use App\Models\SmsMessage;
use App\Models\Transaction;
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

    private function supervisor(): User
    {
        return User::where('email', 'supervisor@moneyagent.local')->firstOrFail();
    }

    private function makeDevice(string $status = 'pending'): Device
    {
        return Device::create([
            'name' => 'Redmi Note 12',
            'agent_id' => cash_point()->id,
            'network_id' => Network::firstOrFail()->id,
            'device_code' => Device::generateDeviceCode(),
            'status' => $status,
        ]);
    }

    public function test_devices_index_is_visible_to_supervisors_and_admins(): void
    {
        $device = $this->makeDevice();

        $this->actingAs($this->admin())
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee('View all SMS');

        $this->actingAs($this->supervisor())
            ->get(route('devices.index'))
            ->assertOk()
            ->assertSee('View all SMS');
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

    public function test_supervisor_can_approve_a_pending_device(): void
    {
        $device = $this->makeDevice();

        $this->actingAs($this->supervisor())
            ->post(route('devices.approve', $device))
            ->assertRedirect();

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'active',
        ]);
    }

    public function test_cashier_cannot_approve_a_device(): void
    {
        $device = $this->makeDevice();

        $this->actingAs($this->cashier())
            ->post(route('devices.approve', $device))
            ->assertForbidden();

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'pending',
        ]);
    }

    public function test_supervisor_sees_approve_button_on_pending_device_page(): void
    {
        $device = $this->makeDevice();

        $this->actingAs($this->supervisor())
            ->get(route('devices.show', $device))
            ->assertOk()
            ->assertSee('Approve & activate')
            ->assertDontSee('Regenerate code')
            ->assertDontSee('Delete');

        $this->actingAs($this->admin())
            ->get(route('devices.show', $device))
            ->assertOk()
            ->assertSee('Regenerate code')
            ->assertSee('Delete');
    }

    public function test_sms_monitor_page_loads_without_parse_error(): void
    {
        $device = $this->makeDevice('active');

        SmsMessage::create([
            'device_id' => $device->id,
            'agent_id' => $device->agent_id,
            'network_id' => $device->network_id,
            'sender' => 'MPESA',
            'message_body' => 'Transaction of TZS 50000.',
            'received_at' => now(),
            'sms_hash' => hash('sha256', 'monitor-test'),
            'server_received_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('sms.index'))
            ->assertOk()
            ->assertSee('SMS Monitor')
            ->assertSee('Transaction of TZS 50000');

        $this->actingAs($this->admin())
            ->get(route('sms.index', ['device' => $device->id]))
            ->assertOk()
            ->assertSee('Transaction of TZS 50000');
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

    public function test_admin_can_revoke_a_device(): void
    {
        $device = $this->makeDevice('active');

        $this->actingAs($this->admin())
            ->post(route('devices.revoke', $device))
            ->assertRedirect();

        $this->assertDatabaseHas('devices', [
            'id' => $device->id,
            'status' => 'revoked',
        ]);
    }

    public function test_admin_can_register_a_device_with_multiple_networks(): void
    {
        $networks = Network::orderBy('id')->take(2)->get();

        $response = $this->actingAs($this->admin())
            ->post(route('devices.store'), [
                'name' => 'Tecno Pova',
                'network_ids' => $networks->pluck('id')->all(),
                'phone_number' => '0712345678',
            ]);

        $response->assertRedirect(route('devices.index'));

        $device = Device::where('name', 'Tecno Pova')->firstOrFail();
        $this->assertSame($networks->first()->id, $device->network_id);

        foreach ($networks->pluck('id') as $networkId) {
            $this->assertDatabaseHas('device_network', [
                'device_id' => $device->id,
                'network_id' => $networkId,
            ]);
        }
    }

    public function test_network_detail_page_lists_transactions_for_that_network(): void
    {
        $network = Network::firstOrFail();
        Transaction::create([
            'reference' => 'NETTXN-'.$network->id,
            'agent_id' => cash_point()->id,
            'network_id' => $network->id,
            'type' => 'deposit',
            'customer_name' => 'Asha Omari',
            'customer_phone' => '0712345678',
            'amount' => 50000,
            'fee' => 0,
            'commission' => 250,
            'status' => 'completed',
        ]);

        $this->actingAs($this->admin())
            ->get(route('networks.show', $network))
            ->assertOk()
            ->assertSee($network->name)
            ->assertSee('Asha Omari')
            ->assertSee('NETTXN-'.$network->id);
    }

    public function test_device_detail_page_loads(): void
    {
        $device = $this->makeDevice('active');

        SmsMessage::create([
            'device_id' => $device->id,
            'agent_id' => $device->agent_id,
            'network_id' => $device->network_id,
            'sender' => 'MPESA',
            'message_body' => 'Test message body',
            'received_at' => now(),
            'sms_hash' => hash('sha256', 'detail-view'),
            'server_received_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get(route('devices.show', $device))
            ->assertOk()
            ->assertSee($device->name)
            ->assertSee('View full SMS');
    }

    public function test_device_detail_page_paginates_all_sms(): void
    {
        $device = $this->makeDevice('active');

        foreach (range(0, 54) as $i) {
            SmsMessage::create([
                'device_id' => $device->id,
                'agent_id' => $device->agent_id,
                'network_id' => $device->network_id,
                'sender' => 'MPESA',
                'message_body' => 'Body '.$i,
                'received_at' => now()->subMinutes(55 - $i),
                'sms_hash' => hash('sha256', 'pager-'.$i),
                'server_received_at' => now()->subMinutes(55 - $i),
            ]);
        }

        $this->actingAs($this->admin())
            ->get(route('devices.show', $device))
            ->assertOk()
            ->assertSee('Body 54')
            ->assertSee('Showing 1–50 of 55')
            ->assertDontSee('Body 0');

        $this->actingAs($this->admin())
            ->get(route('devices.show', ['device' => $device, 'page' => 2]))
            ->assertOk()
            ->assertSee('Body 0')
            ->assertSee('Showing 51–55 of 55')
            ->assertDontSee('Body 54');
    }
}
