<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Network;
use App\Models\SmsMessage;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    private function token(): string
    {
        return 'dv_runtime_test_token';
    }

    private function makeDevice(string $status = 'active', ?Network $network = null): Device
    {
        return Device::create([
            'name' => 'Flutter Phone',
            'agent_id' => cash_point()->id,
            'network_id' => $network?->id ?? Network::firstOrFail()->id,
            'api_token_hash' => hash('sha256', $this->token()),
            'status' => $status,
        ]);
    }

    private function deviceHeaders(Device $device): array
    {
        return ['Authorization' => 'Bearer '.$this->token()];
    }

    public function test_missing_token_is_rejected(): void
    {
        $this->getJson('/api/v1/sms/senders')->assertUnauthorized();
    }

    public function test_non_active_device_cannot_ingest(): void
    {
        $device = $this->makeDevice('pending');

        $this->withHeaders($this->deviceHeaders($device))
            ->postJson('/api/v1/sms/ingest', [
                'sms' => [['sender' => 'MPESA', 'message' => 'body']],
            ])
            ->assertForbidden()
            ->assertJsonPath('device_status', 'pending');
    }

    public function test_senders_endpoint_exposes_the_capture_watchlist(): void
    {
        $device = $this->makeDevice();
        $vodacom = Network::where('code', 'VODACOM')->firstOrFail();
        $airtel = Network::where('code', 'AIRTEL')->firstOrFail();
        $device->networks()->attach([$vodacom->id, $airtel->id]);

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->getJson('/api/v1/sms/senders')
            ->assertOk();

        $senders = collect($response->json('senders'));

        $this->assertNotEmpty($senders);
        $this->assertTrue($senders->contains(fn ($s) => $s['keyword'] === 'MPESA' && $s['network'] === 'VODACOM'));
        $this->assertTrue($senders->contains(fn ($s) => $s['keyword'] === 'AIRTEL' && $s['network'] === 'AIRTEL'));
        $this->assertSame($device->network?->code, $response->json('device.network'));
        $this->assertContains('VODACOM', $response->json('device.networks'));
        $this->assertContains('AIRTEL', $response->json('device.networks'));
        $this->assertSame(500, $response->json('ingest.max_batch'));
    }

    public function test_senders_endpoint_filters_to_device_networks_only(): void
    {
        $device = $this->makeDevice();
        $vodacom = Network::where('code', 'VODACOM')->firstOrFail();
        $device->networks()->attach([$vodacom->id]);

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->getJson('/api/v1/sms/senders')
            ->assertOk();

        $senders = collect($response->json('senders'));

        $this->assertTrue($senders->contains(fn ($s) => $s['keyword'] === 'MPESA'));
        $this->assertFalse($senders->contains(fn ($s) => $s['keyword'] === 'AIRTEL'));
        $this->assertContains('VODACOM', $response->json('device.networks'));
        $this->assertNotContains('AIRTEL', $response->json('device.networks'));
    }

    public function test_unknown_sender_is_stored_but_ignored(): void
    {
        $device = $this->makeDevice('active', Network::where('code', 'VODACOM')->first());

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->postJson('/api/v1/sms/ingest', [
                'sms' => [['sender' => 'TIGO PESA', 'message' => 'TIGO0001 confirmed. You have received TZS 5,000.00 from TANA OMARI 0722333444.']],
            ])
            ->assertOk();

        $this->assertSame(1, $response->json('summary.received'));
        $this->assertSame(1, $response->json('summary.ignored_senders'));
        $this->assertTrue($response->json('results.0.ignored_sender'));

        $this->assertDatabaseHas('sms_messages', [
            'device_id' => $device->id,
            'sender' => 'TIGO PESA',
            'processing_status' => 'failed',
        ]);
        $this->assertSame(0, Transaction::count());
    }

    public function test_known_network_but_unparseable_message_fails(): void
    {
        $device = $this->makeDevice();

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->postJson('/api/v1/sms/ingest', [
                'sms' => [['sender' => 'MPESA', 'message' => 'P98765 Hello there, this is not financial.']],
            ])
            ->assertOk();

        $this->assertSame(1, $response->json('summary.failed'));
        $this->assertSame('SMS did not match any financial template.', $response->json('results.0.error'));
        $this->assertFalse($response->json('results.0.ignored_sender'));
        $this->assertSame(0, Transaction::count());
    }

    public function test_matching_sender_and_message_is_processed_into_a_transaction(): void
    {
        $device = $this->makeDevice();

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->postJson('/api/v1/sms/ingest', [
                'sms' => [[
                    'sender' => 'MPESA',
                    'message' => 'P98765 confirmed. You have received TZS 100,000.00 from JUMA ATHUMANI 0712345678 on 14/9/2026 at 10:30. New balance is TZS 500,000.00.',
                    'received_at' => '2026-09-14 10:30:00',
                ]],
            ])
            ->assertOk();

        $this->assertSame(1, $response->json('summary.received'));
        $this->assertSame(1, $response->json('summary.processed'));

        $sms = SmsMessage::where('device_id', $device->id)->firstOrFail();
        $this->assertSame('processed', $sms->processing_status);
        $this->assertSame('deposit', $sms->transaction_type);
        $this->assertSame(100_000.0, (float) $sms->amount);
        $this->assertSame('JUMA ATHUMANI', $sms->customer_name);
        $this->assertSame('0712345678', $sms->customer_phone);
        $this->assertNotNull($sms->transaction_id);

        $this->assertSame(1, Transaction::count());
        $this->assertDatabaseHas('transactions', [
            'id' => $sms->transaction_id,
            'status' => 'completed',
        ]);
    }

    public function test_duplicate_sms_is_skipped(): void
    {
        $device = $this->makeDevice();

        $body = 'P12345 confirmed. You have received TZS 10,000.00 from AMIR HAMISI 0787654321.';

        $payload = [
            'sms' => [['sender' => 'MPESA', 'message' => $body]],
        ];

        $this->withHeaders($this->deviceHeaders($device))->postJson('/api/v1/sms/ingest', $payload)->assertOk();
        $this->withHeaders($this->deviceHeaders($device))->postJson('/api/v1/sms/ingest', $payload)->assertOk();

        $this->assertSame(1, SmsMessage::where('device_id', $device->id)->count());
    }

    public function test_cashiers_and_staff_cannot_touch_device_api(): void
    {
        $admin = User::where('email', 'admin@moneyagent.local')->firstOrFail();

        $this->actingAs($admin)
            ->getJson('/api/v1/sms/senders')
            ->assertUnauthorized();

        $this->actingAs($admin)
            ->postJson('/api/v1/sms/ingest', ['sms' => []])
            ->assertUnauthorized();
    }
}
