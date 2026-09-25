<?php

namespace Tests\Feature;

use App\Mail\OtpMail;
use App\Models\Setting;
use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_method_sends_a_code_and_shows_only_the_email_challenge(): void
    {
        Mail::fake();
        $user = $this->user(['two_factor_method' => 'email']);
        Setting::create([
            'key' => 'email',
            'value' => [
                'otp_via_email' => '1',
                'otp_enabled' => '1',
            ],
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.show'));

        Mail::assertSent(OtpMail::class);
        $this->assertSame($user->id, session('two_factor_user_id'));
        $this->assertIsString(Cache::get('otp_email_'.$user->id));

        $this->get(route('two-factor.show'))
            ->assertOk()
            ->assertSee('Enter the 6-digit code sent to')
            ->assertSee($user->email)
            ->assertDontSee('authenticator app');
    }

    public function test_authenticator_method_does_not_send_an_email_code(): void
    {
        Mail::fake();
        $user = $this->user(['two_factor_method' => 'app']);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('two-factor.show'));

        Mail::assertNothingSent();
        $this->get(route('two-factor.show'))
            ->assertOk()
            ->assertSee('Enter the 6-digit code from your authenticator app.')
            ->assertDontSee('Resend code');
    }

    public function test_email_code_completes_the_two_factor_login(): void
    {
        Mail::fake();
        $user = $this->user(['two_factor_method' => 'email']);
        Setting::create([
            'key' => 'email',
            'value' => [
                'otp_via_email' => '1',
                'otp_enabled' => '1',
            ],
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $code = Cache::get('otp_email_'.$user->id);

        $this->post(route('two-factor.verify'), ['code' => $code])
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(Auth::check());
        $this->assertSame($user->id, Auth::id());
        $this->assertFalse(Cache::has('otp_email_'.$user->id));
    }

    public function test_recovery_code_completes_authenticator_login(): void
    {
        $recoveryCode = 'ABCD-EFGH-IJKL-MNOP';
        $user = $this->user([
            'two_factor_method' => 'app',
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

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'admin',
            'is_active' => true,
        ], $overrides));
    }
}
