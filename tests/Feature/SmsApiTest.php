<?php

namespace Tests\Feature;

use App\Models\Device;
use App\Models\Network;
use App\Models\NetworkBalance;
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

    private function makeDevice(string $status = 'active', ?Network $network = null): Device
    {
        return Device::create([
            'name' => 'Flutter Phone',
            'agent_id' => $this->cashPoint()->id,
            'network_id' => $network?->id ?? Network::firstOrFail()->id,
            'device_code' => Device::generateDeviceCode(),
            'status' => $status,
        ]);
    }

    private function deviceHeaders(Device $device): array
    {
        return [
            'X-Device-Code' => $device->device_code,
        ];
    }

    public function test_missing_device_code_is_rejected(): void
    {
        $this->getJson('/api/v1/sms/senders')->assertUnauthorized();

        $this->withHeaders(['X-Device-Code' => ''])
            ->getJson('/api/v1/sms/senders')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Missing credentials.');
    }

    public function test_unknown_device_code_is_rejected(): void
    {
        $this->withHeaders(['X-Device-Code' => 'ZZZZZZ'])
            ->getJson('/api/v1/sms/senders')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unknown device. This device is not authorized.');
    }

    public function test_device_code_lookup_is_case_insensitive(): void
    {
        $device = $this->makeDevice();

        $this->withHeaders(['X-Device-Code' => strtolower($device->device_code)])
            ->getJson('/api/v1/sms/senders')
            ->assertOk();
    }

    public function test_blocked_or_revoked_device_is_rejected_with_403(): void
    {
        $device = $this->makeDevice('blocked');

        $this->withHeaders($this->deviceHeaders($device))
            ->getJson('/api/v1/sms/senders')
            ->assertForbidden()
            ->assertJsonPath('message', 'Device is not allowed to access the system.');

        $revoked = $this->makeDevice('revoked');

        $this->withHeaders(['X-Device-Code' => $revoked->device_code])
            ->getJson('/api/v1/sms/senders')
            ->assertForbidden();
    }

    public function test_me_endpoint_requires_the_device_code_and_returns_profile(): void
    {
        $device = $this->makeDevice();

        $this->getJson('/api/v1/devices/me')
            ->assertUnauthorized();

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->getJson('/api/v1/devices/me')
            ->assertOk();

        $this->assertSame($device->device_code, $response->json('device.device_code'));
        $this->assertSame($device->name, $response->json('device.name'));
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

        $this->assertTrue($response->json('capture_all'));
        $this->assertNotEmpty($senders);
        $this->assertTrue($senders->contains(fn ($s) => $s['keyword'] === 'MPESA' && $s['network'] === 'VODACOM'));
        $this->assertTrue($senders->contains(fn ($s) => $s['keyword'] === 'AIRTEL' && $s['network'] === 'AIRTEL'));
        $this->assertSame($device->network?->code, $response->json('device.network'));
        $this->assertContains('VODACOM', $response->json('device.networks'));
        $this->assertContains('AIRTEL', $response->json('device.networks'));
        $this->assertSame(500, $response->json('ingest.max_batch'));
    }

    public function test_senders_endpoint_exposes_device_lines(): void
    {
        $device = $this->makeDevice();
        $tigo = Network::where('code', 'TIGOPESA')->firstOrFail();

        $device->lines()->create([
            'sim_slot' => 1,
            'network_id' => $tigo->id,
            'phone_number' => '0755123456',
        ]);

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->getJson('/api/v1/sms/senders')
            ->assertOk();

        $this->assertSame(1, count($response->json('lines')));
        $this->assertSame(1, $response->json('lines.0.sim_slot'));
        $this->assertSame('TIGOPESA', $response->json('lines.0.network'));
        $this->assertSame('0755123456', $response->json('lines.0.phone_number'));
    }

    public function test_ingest_attributes_sms_to_the_sim_line_matching_the_slot(): void
    {
        $device = $this->makeDevice('active', Network::where('code', 'VODACOM')->first());
        $tigo = Network::where('code', 'TIGOPESA')->firstOrFail();

        $line = $device->lines()->create([
            'sim_slot' => 1,
            'network_id' => $tigo->id,
            'phone_number' => '0755123456',
        ]);

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->postJson('/api/v1/sms/ingest', [
                'sms' => [[
                    'sender' => 'TIGO PESA',
                    'sim_slot' => 1,
                    'message' => 'TIGO0001 confirmed. You have received TZS 5,000.00 from TANA OMARI 0722333444.',
                ]],
            ])
            ->assertOk();

        $this->assertSame(1, $response->json('summary.processed'));
        $this->assertSame('TIGOPESA', $response->json('results.0.network'));
        $this->assertSame(1, $response->json('results.0.sim_slot'));

        $sms = SmsMessage::where('device_id', $device->id)->firstOrFail();
        $this->assertSame($line->id, $sms->device_line_id);
        $this->assertSame(1, $sms->sim_slot);
        $this->assertSame($tigo->id, $sms->network_id);
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

    public function test_unknown_sender_is_accepted_and_attributed_to_device_network(): void
    {
        $device = $this->makeDevice('active', Network::where('code', 'VODACOM')->first());

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->postJson('/api/v1/sms/ingest', [
                'sms' => [['sender' => 'TATU BANK', 'message' => 'P1234 confirmed. You have received TZS 5,000.00 from TANA OMARI 0722333444.']],
            ])
            ->assertOk();

        $this->assertSame(1, $response->json('summary.received'));
        $this->assertSame(1, $response->json('summary.processed'));
        $this->assertSame(0, $response->json('summary.ignored_senders'));
        $this->assertArrayNotHasKey('ignored_sender', $response->json('results.0'));

        $sms = SmsMessage::where('device_id', $device->id)->firstOrFail();
        $this->assertSame('RECORDED', $sms->processing_status);
        $this->assertSame('TATU BANK', $sms->sender);
        $this->assertSame('bank', $sms->provider);
        $this->assertSame('VODACOM', $sms->network?->code);
        $this->assertNotNull($sms->transaction_id);
        $this->assertSame(1, Transaction::count());
    }

    public function test_unknown_sender_with_non_financial_message_is_stored_but_not_processed(): void
    {
        $device = $this->makeDevice('active', Network::where('code', 'VODACOM')->first());

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->postJson('/api/v1/sms/ingest', [
                'sms' => [['sender' => 'TATU BANK', 'message' => 'Your OTP for login is 123456. Do not share it.']],
            ])
            ->assertOk();

        $this->assertSame(1, $response->json('summary.received'));
        $this->assertSame(0, $response->json('summary.ignored_senders'));
        $this->assertSame('SMS did not match any financial template.', $response->json('results.0.error'));

        $this->assertDatabaseHas('sms_messages', [
            'device_id' => $device->id,
            'sender' => 'TATU BANK',
            'processing_status' => 'NEEDS_REVIEW',
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
        $this->assertSame('RECORDED', $sms->processing_status);
        $this->assertSame('withdrawal', $sms->transaction_type);
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

    public function test_tigo_swahili_message_is_parsed_by_the_tigo_parser(): void
    {
        $device = $this->makeDevice('active', Network::where('code', 'TIGOPESA')->first());

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->postJson('/api/v1/sms/ingest', [
                'sms' => [[
                    'sender' => 'TIGO PESA',
                    'message' => 'Tigo Pesa: Umepokea TZS 50,000 kutoka kwa JOHN DOE 0712345678. Transaction ID: MP250920ABC123. Salio lako ni TZS 350,000.',
                ]],
            ])
            ->assertOk();

        $this->assertSame(1, $response->json('summary.processed'));

        $sms = SmsMessage::where('device_id', $device->id)->firstOrFail();
        $this->assertSame('tigo', $sms->provider);
        $this->assertSame('TIGOPESA', $sms->network?->code);
        $this->assertSame('RECORDED', $sms->processing_status);
        $this->assertSame('withdrawal', $sms->transaction_type);
        $this->assertSame('MP250920ABC123', $sms->transaction_reference);
        $this->assertSame(50_000.0, (float) $sms->amount);
        $this->assertSame('JOHN DOE', $sms->customer_name);
        $this->assertSame('0712345678', $sms->customer_phone);
        $this->assertSame(350_000.0, (float) $sms->balance);
        $this->assertSame(1, Transaction::count());
    }

    public function test_same_provider_reference_is_only_recorded_once_across_devices(): void
    {
        $network = Network::where('code', 'VODACOM')->first();
        $first = $this->makeDevice('active', $network);
        $second = $this->makeDevice('active', $network);

        $bodyA = 'P98765 confirmed. You have received TZS 100,000.00 from JUMA ATHUMANI 0712345678.';
        $bodyB = 'P98765 confirmed. You have received TZS 100,000.00 from JUMA ATHUMANI 0712345678 today.';

        $this->withHeaders($this->deviceHeaders($first))
            ->postJson('/api/v1/sms/ingest', ['sms' => [['sender' => 'MPESA', 'message' => $bodyA]]])
            ->assertOk();

        $response = $this->withHeaders($this->deviceHeaders($second))
            ->postJson('/api/v1/sms/ingest', ['sms' => [['sender' => 'MPESA', 'message' => $bodyB]]])
            ->assertOk();

        $this->assertTrue($response->json('results.0.duplicate'));
        $this->assertSame(1, Transaction::count());

        $duplicate = SmsMessage::where('device_id', $second->id)->firstOrFail();
        $this->assertTrue($duplicate->is_duplicate);
        $this->assertSame('DUPLICATE', $duplicate->processing_status);
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

    public function test_mixx_customer_deposit_decreases_float_and_increases_cash(): void
    {
        $device = $this->makeDevice();
        $agent = $this->cashPoint();
        $network = $device->network()->firstOrFail();

        $balance = NetworkBalance::firstOrCreate(
            ['agent_id' => $agent->id, 'network_id' => $network->id],
            ['opening_balance' => 1_114_000, 'balance' => 1_114_000],
        );

        $initialFloat = (float) $balance->balance;
        $initialCash = (float) $agent->cash_balance;

        $response = $this->withHeaders($this->deviceHeaders($device))
            ->postJson('/api/v1/sms/ingest', [
                'sms' => [[
                    'sender' => 'MIXX BY YAS',
                    'received_at' => '2026-09-21 17:15:00',
                    'message' => 'Zoezi la kuweka fedha kwa HALMA RASHIDI, 255676885670 limefanikiwa. Kiasi Tsh 5,000. Mrejaa Tsh 99. Salio Jipya ni Tsh 1,109,000. Kumbukumbu No: 26363415048141. 21/09/26 17:15.',
                ]],
            ])
            ->assertOk();

        $this->assertSame(1, $response->json('summary.received'));
        $this->assertSame(1, $response->json('summary.processed'));

        $txn = Transaction::firstOrFail();
        $this->assertSame('deposit', $txn->type);
        $this->assertSame(5_000.0, (float) $txn->amount);
        $this->assertSame('26363415048141', $txn->provider_reference);
        $this->assertSame('HALMA RASHIDI', $txn->customer_name);
        $this->assertSame('255676885670', $txn->customer_phone);

        $balance->refresh();
        $agent->refresh();

        $this->assertSame($initialFloat - 5_000, (float) $balance->balance);
        $this->assertSame($initialCash + 5_000, (float) $agent->cash_balance);
    }

    public function test_bootstrap_records_a_phone_session(): void
    {
        $device = $this->makeDevice();

        $this->withHeaders($this->deviceHeaders($device))
            ->postJson('/api/v1/devices/bootstrap', [
                'device_uid' => 'UID-001',
                'model' => 'Samsung A15',
                'android_version' => '14',
                'app_version' => '1.0.2',
            ])
            ->assertOk();

        $this->assertDatabaseHas('device_phones', [
            'device_id' => $device->id,
            'device_uid' => 'UID-001',
            'model' => 'Samsung A15',
            'app_version' => '1.0.2',
        ]);
    }

    public function test_heartbeat_upserts_a_single_phone_session_per_handset(): void
    {
        $device = $this->makeDevice();

        for ($i = 1; $i <= 3; $i++) {
            $this->withHeaders($this->deviceHeaders($device))
                ->postJson('/api/v1/heartbeat', [
                    'device_uid' => 'UID-007',
                    'model' => $i === 3 ? 'Xiaomi Redmi' : 'Old Model',
                ])
                ->assertOk();
        }

        $this->assertSame(1, $device->phones()->count());
        $this->assertDatabaseHas('device_phones', [
            'device_id' => $device->id,
            'device_uid' => 'UID-007',
            'model' => 'Xiaomi Redmi',
        ]);
    }
}
