<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsOutboundTest extends TestCase
{
    use RefreshDatabase;

    private const TOKEN = 'test-provider-token';

    public function test_admin_can_save_encrypted_sms_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('settings.store'), [
                'sms' => [
                    'sender_id' => 'TANZANIATIP',
                    'authorization_token' => 'Bearer '.self::TOKEN,
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $setting = Setting::where('key', 'sms')->firstOrFail();

        $this->assertSame(['sender_id' => 'TANZANIATIP'], $setting->value);
        $this->assertSame(self::TOKEN, $setting->sms_authorization_token);
        $this->assertNotSame(self::TOKEN, DB::table('settings')->where('key', 'sms')->value('sms_authorization_token'));

        $this->actingAs($admin)
            ->get(route('settings.sms'))
            ->assertOk()
            ->assertSee('TANZANIATIP')
            ->assertSee('Authorization bearer token (configured)')
            ->assertDontSee(self::TOKEN, false);
    }

    public function test_sms_token_is_required_for_new_configuration_and_preserved_when_omitted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->postJson(route('settings.store'), [
                'sms' => ['sender_id' => 'TANZANIATIP'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('sms.authorization_token');

        $this->saveSmsSettings($admin);

        $this->actingAs($admin)
            ->postJson(route('settings.store'), [
                'sms' => ['sender_id' => 'UPDATED'],
            ])
            ->assertOk();

        $setting = Setting::where('key', 'sms')->firstOrFail();
        $this->assertSame(['sender_id' => 'UPDATED'], $setting->value);
        $this->assertSame(self::TOKEN, $setting->sms_authorization_token);
    }

    public function test_admin_can_check_sms_provider_connection(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/v2/balance' => Http::response(['balance' => 100], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->saveSmsSettings($admin);

        $this->actingAs($admin)
            ->getJson(route('settings.sms.connection'))
            ->assertOk()
            ->assertJson([
                'connected' => true,
                'message' => 'SMS provider connection verified.',
            ]);

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === 'https://messaging-service.co.tz/api/v2/balance'
                && in_array('Bearer '.self::TOKEN, $request->header('Authorization'), true);
        });
    }

    public function test_sms_connection_check_reports_missing_configuration_without_provider_request(): void
    {
        Http::preventStrayRequests();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->getJson(route('settings.sms.connection'))
            ->assertStatus(422)
            ->assertJson([
                'connected' => false,
                'message' => 'Save the sender ID and authorization token before checking the connection.',
            ]);

        Http::assertNothingSent();
    }

    public function test_sms_connection_failures_return_a_safe_error_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/v2/balance' => Http::response(['message' => 'Invalid provider token'], 401),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->saveSmsSettings($admin);

        $response = $this->actingAs($admin)
            ->getJson(route('settings.sms.connection'))
            ->assertStatus(502)
            ->assertJson([
                'connected' => false,
                'message' => 'The SMS provider could not be reached. Check the API settings and try again.',
            ]);

        $response->assertDontSee(self::TOKEN, false);
        $response->assertJsonMissing(['message' => 'Invalid provider token']);
    }

    public function test_non_admin_cannot_check_sms_provider_connection(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->getJson(route('settings.sms.connection'))
            ->assertForbidden();
    }

    public function test_single_sms_is_sent_with_saved_credentials(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['status' => 'queued'], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->saveSmsSettings($admin);

        $this->actingAs($admin)
            ->postJson(route('settings.sms.send'), [
                'to' => '0716718040',
                'text' => 'Hello from the store',
                'reference' => 'test_123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('provider_response.status', 'queued');

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === 'https://messaging-service.co.tz/api/sms/v2/text/single'
                && in_array('Bearer '.self::TOKEN, $request->header('Authorization'), true)
                && $request->data() === [
                    'from' => 'TANZANIATIP',
                    'to' => '255716718040',
                    'text' => 'Hello from the store',
                    'flash' => 0,
                    'reference' => 'test_123',
                ];
        });
    }

    public function test_bulk_sms_is_sent_with_normalized_recipients(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/multi' => Http::response(['status' => 'accepted'], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->saveSmsSettings($admin);

        $this->actingAs($admin)
            ->postJson(route('settings.sms.send-bulk'), [
                'recipients' => '255716718040, 0716718041',
                'text' => 'Bulk hello',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('recipient_count', 2);

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === 'https://messaging-service.co.tz/api/sms/v2/text/multi'
                && in_array('Bearer '.self::TOKEN, $request->header('Authorization'), true)
                && $request->data()['messages'] === [
                    ['to' => '255716718040', 'text' => 'Bulk hello'],
                    ['to' => '255716718041', 'text' => 'Bulk hello'],
                ];
        });
    }

    public function test_non_admin_cannot_send_outbound_sms(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->postJson(route('settings.sms.send'), [
                'to' => '255716718040',
                'text' => 'Unauthorized',
            ])
            ->assertForbidden();
    }

    public function test_invalid_single_recipient_is_rejected_without_a_provider_request(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['status' => 'queued'], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->saveSmsSettings($admin);

        $this->actingAs($admin)
            ->postJson(route('settings.sms.send'), [
                'to' => 'not-a-phone-number',
                'text' => 'Invalid recipient',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('to');

        Http::assertNothingSent();
    }

    public function test_provider_failures_return_a_safe_error_response(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['message' => 'Rejected'], 422),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->saveSmsSettings($admin);

        $this->actingAs($admin)
            ->postJson(route('settings.sms.send'), [
                'to' => '255716718040',
                'text' => 'This will fail',
            ])
            ->assertStatus(502)
            ->assertJson([
                'success' => false,
                'message' => 'SMS could not be sent. Check the provider response and SMS settings.',
            ]);
    }

    private function saveSmsSettings(User $admin): void
    {
        $this->actingAs($admin)
            ->postJson(route('settings.store'), [
                'sms' => [
                    'sender_id' => 'TANZANIATIP',
                    'authorization_token' => self::TOKEN,
                ],
            ])
            ->assertOk();
    }
}
