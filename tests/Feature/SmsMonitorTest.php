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
            'agent_id' => $this->cashPoint()->id,
            'network_id' => $network->id,
            'device_code' => Device::generateDeviceCode(),
            'status' => 'active',
        ]);

        return SmsMessage::create(array_merge([
            'device_id' => $device->id,
            'sender' => 'MPESA',
            'message_body' => 'Hello world, not a financial message.',
            'sms_hash' => hash('sha256', $overrides['message_body'] ?? 'Hello world, not a financial message.'),
            'processing_status' => 'NEEDS_REVIEW',
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

        $this->assertSame('stored', sms_status_label('NEEDS_REVIEW', false, 'SMS did not match any financial template.'));
        $this->assertSame('tag-terracotta', sms_status_badge('NEEDS_REVIEW', false, 'SMS did not match any financial template.'));
    }

    public function test_genuine_failure_is_not_relabelled(): void
    {
        $this->makeMessage(['processing_error' => 'Device has no network assigned.']);

        $this->actingAs($this->admin())
            ->get(route('sms.index'))
            ->assertOk()
            ->assertSee('Failed');

        $this->assertSame('failed', sms_status_label('FAILED', false, 'Device has no network assigned.'));
        $this->assertSame('tag-red', sms_status_badge('FAILED', false, 'Device has no network assigned.'));
    }

    public function test_processed_and_duplicate_helpers(): void
    {
        $this->assertSame('processed', sms_status_label('RECORDED'));
        $this->assertSame('tag-green', sms_status_badge('RECORDED'));

        $this->assertSame('duplicate', sms_status_label('RECORDED', true));
        $this->assertSame('tag-terracotta', sms_status_badge('RECORDED', true));
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

    public function test_status_tabs_filter_stored_and_duplicate_messages(): void
    {
        $this->makeMessage(); // stored (template mismatch)

        $this->makeMessage([
            'processing_status' => 'FAILED',
            'processing_error' => 'Device has no network assigned.',
            'is_duplicate' => false,
            'message_body' => 'Genuine failure body',
            'sms_hash' => hash('sha256', 'genuine-failure'),
        ]);

        $this->makeMessage([
            'processing_status' => 'RECORDED',
            'is_duplicate' => true,
            'message_body' => 'Duplicate body',
            'sms_hash' => hash('sha256', 'duplicate-body'),
        ]);

        $this->actingAs($this->admin())
            ->get(route('sms.index', ['status' => 'stored']))
            ->assertOk()
            ->assertSee('Stored')
            ->assertDontSee('Genuine failure body')
            ->assertDontSee('Duplicate body');

        $this->actingAs($this->admin())
            ->get(route('sms.index', ['status' => 'duplicate']))
            ->assertOk()
            ->assertSee('Duplicate body')
            ->assertDontSee('Genuine failure body');

        $this->actingAs($this->admin())
            ->get(route('sms.index', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('Genuine failure body')
            ->assertDontSee('Duplicate body');
    }

    public function test_status_tabs_render_count_badges(): void
    {
        $this->makeMessage();

        $this->actingAs($this->admin())
            ->get(route('sms.index'))
            ->assertOk()
            ->assertSee('Processed')
            ->assertSee('Pending')
            ->assertSee('Stored')
            ->assertSee('Failed')
            ->assertSee('Duplicates');
    }
}
