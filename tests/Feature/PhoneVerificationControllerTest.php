<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PhoneVerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_a_verification_code_to_the_verified_normalized_number(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['status' => 'sent'], 200),
        ]);
        $user = $this->user(['phone' => '0712345678']);
        $this->saveSmsSettings();

        $response = $this->actingAs($user)->postJson(route('account.phone.verification.store'));

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'A verification code was sent to 255712345678.',
        ]);

        $stored = Cache::get('phone_verification_'.$user->id);
        $this->assertSame('255712345678', $stored['phone']);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $stored['code']);

        Http::assertSent(function (ClientRequest $request) use ($stored): bool {
            $data = $request->data();

            return $request->url() === 'https://messaging-service.co.tz/api/sms/v2/text/single'
                && ($data['to'] ?? null) === '255712345678'
                && str_contains($data['text'] ?? '', $stored['code']);
        });
    }

    public function test_does_not_resend_a_code_for_an_already_verified_number(): void
    {
        Http::preventStrayRequests();
        $user = $this->user(['phone' => '0712345678', 'phone_verified_at' => now()]);

        $response = $this->actingAs($user)->postJson(route('account.phone.verification.store'));

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'This mobile number is already verified.',
        ]);
        Http::assertNothingSent();
    }

    public function test_reports_an_unavailable_provider_without_a_request(): void
    {
        Http::preventStrayRequests();
        $user = $this->user(['phone' => '0712345678']);

        $response = $this->actingAs($user)->postJson(route('account.phone.verification.store'));

        $response->assertUnprocessable()->assertJson([
            'success' => false,
            'message' => 'Phone verification is currently unavailable. Ask an administrator to configure the SMS provider.',
        ]);
        Http::assertNothingSent();
    }

    public function test_rejects_an_invalid_phone_without_a_provider_request(): void
    {
        Http::preventStrayRequests();
        $user = $this->user(['phone' => 'not-a-number']);
        $this->saveSmsSettings();

        $response = $this->actingAs($user)->postJson(route('account.phone.verification.store'));

        $response->assertUnprocessable()->assertJson([
            'success' => false,
            'message' => 'Add a valid mobile number to your profile before verification.',
        ]);
        Http::assertNothingSent();
    }

    public function test_reports_a_provider_failure_without_storing_a_code(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['message' => 'Rejected'], 500),
        ]);
        $user = $this->user(['phone' => '0712345678']);
        $this->saveSmsSettings();

        $response = $this->actingAs($user)->postJson(route('account.phone.verification.store'));

        $response->assertStatus(500)->assertJson([
            'success' => false,
            'message' => 'Could not send the verification code. Please try again.',
        ]);
        $this->assertFalse(Cache::has('phone_verification_'.$user->id));
    }

    public function test_verifies_a_matching_code_and_marks_the_number_verified(): void
    {
        $user = $this->user(['phone' => '0712345678']);
        Cache::put('phone_verification_'.$user->id, ['code' => '123456', 'phone' => '255712345678'], 300);

        $response = $this->actingAs($user)->postJson(route('account.phone.verify'), ['code' => '123456']);

        $response->assertOk()->assertJson([
            'success' => true,
            'message' => 'Mobile number verified successfully.',
        ]);
        $this->assertNotNull($user->fresh()->phone_verified_at);
        $this->assertFalse(Cache::has('phone_verification_'.$user->id));
    }

    public function test_rejects_a_code_sent_to_a_previous_number(): void
    {
        $user = $this->user(['phone' => '0712345678']);
        Cache::put('phone_verification_'.$user->id, ['code' => '123456', 'phone' => '255700000000'], 300);

        $response = $this->actingAs($user)->postJson(route('account.phone.verify'), ['code' => '123456']);

        $response->assertUnprocessable()->assertJson([
            'success' => false,
            'message' => 'The verification code is invalid or has expired.',
        ]);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_rejects_an_invalid_verification_code(): void
    {
        $user = $this->user(['phone' => '0712345678']);
        Cache::put('phone_verification_'.$user->id, ['code' => '123456', 'phone' => '255712345678'], 300);

        $response = $this->actingAs($user)->postJson(route('account.phone.verify'), ['code' => '654321']);

        $response->assertUnprocessable()->assertJson([
            'success' => false,
            'message' => 'The verification code is invalid or has expired.',
        ]);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_returns_401_when_verification_has_no_authentication(): void
    {
        $this->postJson(route('account.phone.verification.store'))->assertUnauthorized();
    }

    public function test_returns_422_when_verification_code_has_the_wrong_length(): void
    {
        $user = $this->user(['phone' => '0712345678']);

        $this->actingAs($user)
            ->postJson(route('account.phone.verify'), ['code' => '123'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('code');
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
            'is_active' => true,
        ], $overrides));
    }

    private function saveSmsSettings(): void
    {
        Setting::create([
            'key' => 'sms',
            'value' => ['sender_id' => 'WAKALA'],
            'sms_authorization_token' => 'test-sms-token',
        ]);
    }
}
