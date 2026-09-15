<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Network;
use App\Models\SmsMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsMonitorTest extends TestCase
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

    private function makeMessage(array $overrides = []): SmsMessage
    {
        $network = Network::first();

        $device = Device::create([
            'name' => 'Redmi Note 12',
            'agent_id' => cash_point()->id,
            'network_id' => $network->id,
            'device_code' => Device::generateDeviceCode(),
            'status' => 'active',
        ]);

        return SmsMessage::create(array_merge([
            'device_id' => $device->id,
            'sender' => 'MPESA',
            'message_body' => 'Hello world, not a financial message.',
            'sms_hash' => hash('sha256', $overrides['message_body'] ?? 'Hello world, not a financial message.'),
            'processing_status' => 'failed',
            'processing_error' => 'SMS did not match any financial template.',
            'is_duplicate' => false,
            'server_received_at' => now(),
        ], $overrides));
    }

    public function test_template_mismatch_failed_message_is_relabelled_stored(): void
    {
        $message = $this->makeMessage();

        $response = $this->actingAs($this->admin())
            ->get(route('sms.index'))
            ->assertOk();

        $response->assertSee('Stored');
        $response->assertSee('SMS did not match any financial template.');

        $this->assertSame('stored', sms_status_label('failed', false, 'SMS did not match any financial template.'));
        $this->assertSame('tag-terracotta', sms_status_badge('failed', false, 'SMS did not match any financial template.'));
    }

    public function test_genuine_failure_is_not_relabelled(): void
    {
        $this->makeMessage(['processing_error' => 'Device has no network assigned.']);

        $this->actingAs($this->admin())
            ->get(route('sms.index'))
            ->assertOk()
            ->assertSee('Failed');

        $this->assertSame('failed', sms_status_label('failed', false, 'Device has no network assigned.'));
        $this->assertSame('tag-red', sms_status_badge('failed', false, 'Device has no network assigned.'));
    }

    public function test_processed_and_duplicate_helpers(): void
    {
        $this->assertSame('processed', sms_status_label('processed'));
        $this->assertSame('tag-green', sms_status_badge('processed'));

        $this->assertSame('duplicate', sms_status_label('failed', true));
        $this->assertSame('tag-terracotta', sms_status_badge('failed', true));
    }

    public function test_failed_stat_only_counts_genuine_failures(): void
    {
        $this->makeMessage();
        $this->makeMessage(['processing_error' => 'Device has no network assigned.']);

        $response = $this->actingAs($this->admin())
            ->get(route('sms.index'))
            ->assertOk();

        // Exactly one genuine failure + one stored message.
        $response->assertSee('Stored · no transaction');
    }
}
