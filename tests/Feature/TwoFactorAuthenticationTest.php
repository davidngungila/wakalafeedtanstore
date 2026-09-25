<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sms_method_sends_a_code_and_shows_only_the_sms_challenge(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['status' => 'sent'], 200),
        ]);
        Mail::fake();
        $user = $this->user(['two_factor_method' => 'sms', 'phone' => '0712345678']);
        $this->saveSmsSettings();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.show'));

        Mail::assertNothingSent();
        Http::assertSent(function (ClientRequest $request) use ($user): bool {
            $data = $request->data();
            $code = Cache::get('otp_sms_'.$user->id);

            return $request->url() === 'https://messaging-service.co.tz/api/sms/v2/text/single'
                && ($data['to'] ?? null) === '255712345678'
                && is_string($code)
                && str_contains($data['text'] ?? '', $code);
        });
        $this->assertSame($user->id, session('two_factor_user_id'));
        $this->assertIsString(Cache::get('otp_sms_'.$user->id));

        $this->get(route('two-factor.show'))
            ->assertOk()
            ->assertSee('Enter the 6-digit code sent to')
            ->assertSee('0712345678')
            ->assertSee('Resend code')
            ->assertDontSee('authenticator app');
    }

    public function test_authenticator_method_does_not_send_an_sms_code(): void
    {
        Http::preventStrayRequests();
        $user = $this->user(['two_factor_method' => 'app', 'two_factor_app_enabled' => true]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.show'));

        Http::assertNothingSent();
        $this->get(route('two-factor.show'))
            ->assertOk()
            ->assertSee('Enter the 6-digit code from your authenticator app.')
            ->assertDontSee('Resend code');
    }

    public function test_sms_code_completes_the_two_factor_login(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['status' => 'sent'], 200),
        ]);
        $user = $this->user(['two_factor_method' => 'sms', 'phone' => '0712345678']);
        $this->saveSmsSettings();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $code = Cache::get('otp_sms_'.$user->id);

        $this->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
        $this->assertFalse(Cache::has('otp_sms_'.$user->id));
    }

    public function test_sms_login_fails_when_the_provider_is_not_configured(): void
    {
        Http::preventStrayRequests();
        $user = $this->user(['two_factor_method' => 'sms', 'phone' => '0712345678']);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'SMS login codes are unavailable. Ask an administrator to configure the SMS provider.']);
        Http::assertNothingSent();
        $response->assertSessionMissing('two_factor_user_id');
    }

    public function test_sms_login_fails_when_the_user_phone_is_invalid(): void
    {
        Http::preventStrayRequests();
        $user = $this->user(['two_factor_method' => 'sms', 'phone' => 'not-a-number']);
        $this->saveSmsSettings();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'SMS login codes are unavailable. Ask an administrator to update your mobile number.']);
        Http::assertNothingSent();
        $response->assertSessionMissing('two_factor_user_id');
    }

    public function test_sms_login_fails_when_the_provider_rejects_the_code(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['message' => 'Rejected'], 500),
        ]);
        $user = $this->user(['two_factor_method' => 'sms', 'phone' => '0712345678']);
        $this->saveSmsSettings();

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors(['email' => 'Could not send the SMS login code. Please try again.']);
        $this->assertFalse(Cache::has('otp_sms_'.$user->id));
        $response->assertSessionMissing('two_factor_user_id');
    }

    public function test_sms_code_can_be_resent_to_the_user_phone(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['status' => 'sent'], 200),
        ]);
        $user = $this->user(['two_factor_method' => 'sms', 'phone' => '0712345678']);
        $this->saveSmsSettings();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.show'));

        $this->postJson(route('two-factor.resend'))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'A new code was sent to 255712345678.',
            ]);

        $this->assertIsString(Cache::get('otp_sms_'.$user->id));
    }

    public function test_sms_resend_reports_an_unavailable_provider_without_a_request(): void
    {
        Http::preventStrayRequests();
        $user = $this->user(['two_factor_method' => 'sms', 'phone' => '0712345678']);

        $this->withSession(['two_factor_user_id' => $user->id])
            ->postJson(route('two-factor.resend'))
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'SMS OTP is currently unavailable.',
            ]);

        Http::assertNothingSent();
    }

    public function test_legacy_email_method_falls_back_to_the_authenticator_challenge(): void
    {
        Http::preventStrayRequests();
        $user = $this->user(['two_factor_method' => 'email', 'two_factor_app_enabled' => true]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.show'));

        Http::assertNothingSent();
        $this->get(route('two-factor.show'))
            ->assertOk()
            ->assertSee('Enter the 6-digit code from your authenticator app.');
    }

    public function test_sms_two_factor_can_be_enabled_with_a_valid_phone(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'phone' => '0712345678',
            'phone_verified_at' => now(),
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
        $this->saveSmsSettings();

        $response = $this->actingAs($user)->postJson(route('account.two-factor.enable-sms'));

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame('sms', $user->fresh()->two_factor_method);
    }

    public function test_sms_two_factor_rejects_an_invalid_phone_without_a_provider_request(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
            'phone' => 'not-a-number',
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
        $this->saveSmsSettings();

        $response = $this->actingAs($user)->postJson(route('account.two-factor.enable-sms'));

        $response->assertUnprocessable()->assertJson([
            'success' => false,
            'message' => 'Verify your mobile number before using SMS OTP. A configured SMS provider is also required.',
        ]);
        Http::assertNothingSent();
        $this->assertNull($user->fresh()->two_factor_method);
    }

    public function test_sms_two_factor_method_rejects_email_as_unavailable(): void
    {
        $user = $this->user(['two_factor_method' => 'app']);

        $response = $this->actingAs($user)->postJson(route('account.two-factor.method'), ['method' => 'email']);

        $response->assertUnprocessable()->assertJsonValidationErrors('method');
        $this->assertSame('app', $user->fresh()->two_factor_method);
    }

    public function test_recovery_code_completes_authenticator_login(): void
    {
        $recoveryCode = 'ABCD-EFGH-IJKL-MNOP';
        $user = $this->user([
            'two_factor_method' => 'app',
            'two_factor_app_enabled' => true,
            'two_factor_recovery_codes' => [TwoFactor::hashRecoveryCode($recoveryCode)],
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->post(route('two-factor.verify'), ['code' => $recoveryCode])
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
        $this->assertEmpty($user->fresh()->two_factor_recovery_codes);
    }

    public function test_both_methods_are_available_with_sms_selected_by_default(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['status' => 'sent'], 200),
        ]);
        $user = $this->user([
            'two_factor_method' => 'sms',
            'phone' => '0712345678',
            'two_factor_app_enabled' => true,
        ]);
        $this->saveSmsSettings();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.show'));

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->url() === 'https://messaging-service.co.tz/api/sms/v2/text/single';
        });

        $this->get(route('two-factor.show'))
            ->assertOk()
            ->assertSee('SMS verification is selected by default')
            ->assertSee('data-otp-method="sms"', false)
            ->assertSee('data-otp-method="app"', false)
            ->assertSee('id="otpMethod" value="sms"', false);
    }

    public function test_preferred_authenticator_method_does_not_send_sms_when_both_are_available(): void
    {
        Http::preventStrayRequests();
        $user = $this->user([
            'two_factor_method' => 'app',
            'phone' => '0712345678',
            'two_factor_app_enabled' => true,
        ]);
        $this->saveSmsSettings();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.show'));

        Http::assertNothingSent();

        $this->get(route('two-factor.show'))
            ->assertOk()
            ->assertSee('data-otp-method="sms"', false)
            ->assertSee('data-otp-method="app"', false)
            ->assertSee('id="otpMethod" value="app"', false);
    }

    public function test_authenticator_recovery_code_works_when_sms_is_also_available(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://messaging-service.co.tz/api/sms/v2/text/single' => Http::response(['status' => 'sent'], 200),
        ]);
        $recoveryCode = 'ABCD-EFGH-IJKL-MNOP';
        $user = $this->user([
            'two_factor_method' => 'sms',
            'phone' => '0712345678',
            'two_factor_app_enabled' => true,
            'two_factor_recovery_codes' => [TwoFactor::hashRecoveryCode($recoveryCode)],
        ]);
        $this->saveSmsSettings();

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->post(route('two-factor.verify'), ['method' => 'app', 'code' => $recoveryCode])
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
    }

    public function test_switching_default_to_sms_preserves_the_authenticator_channel(): void
    {
        $user = $this->user([
            'two_factor_method' => 'app',
            'phone' => '0712345678',
            'two_factor_app_enabled' => true,
        ]);
        $this->saveSmsSettings();

        $this->actingAs($user)
            ->postJson(route('account.two-factor.method'), ['method' => 'sms'])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('sms', $user->fresh()->two_factor_method);
        $this->assertTrue($user->fresh()->two_factor_app_enabled);
    }

    public function test_authenticator_setup_preserves_sms_as_the_default(): void
    {
        $secret = 'JBSWY3DPEHPK3PXP';
        $user = $this->user([
            'two_factor_method' => 'sms',
            'phone' => '0712345678',
            'two_factor_app_enabled' => false,
        ]);
        $this->saveSmsSettings();

        $response = $this->withSession(['two_factor_pending_secret' => $secret])
            ->actingAs($user)
            ->postJson(route('account.two-factor.confirm'), ['code' => $this->totpCode($secret)]);

        $response->assertOk()->assertJsonPath('success', true);

        $fresh = $user->fresh();
        $this->assertTrue($fresh->two_factor_app_enabled);
        $this->assertSame('sms', $fresh->two_factor_method);
        $this->assertSame($secret, Crypt::decryptString($fresh->two_factor_secret));
    }

    private function totpCode(string $secret): string
    {
        $binary = TwoFactor::base32Decode($secret);
        $counter = intdiv(time(), 30);

        for ($offset = -1; $offset <= 1; $offset++) {
            $hash = hash_hmac('sha1', pack('J', $counter + $offset), $binary, true);
            $code = str_pad((string) ((unpack('N', substr($hash, ord($hash[19]) & 0x0F, 4))[1] & 0x7FFFFFFF) % 1000000), 6, '0', STR_PAD_LEFT);

            if (TwoFactor::verifyCode($secret, $code)) {
                return $code;
            }
        }

        $this->fail('Unable to generate a valid authenticator code for the test secret.');
    }

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
            'is_active' => true,
            'phone_verified_at' => now(),
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
