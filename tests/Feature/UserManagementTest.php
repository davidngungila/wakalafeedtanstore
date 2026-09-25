<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_page_shows_phone_verification_and_two_factor_methods(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->saveSmsSettings();
        $verified = User::factory()->create([
            'role' => 'cashier',
            'phone' => '0712345678',
            'phone_verified_at' => now(),
            'two_factor_method' => 'sms',
            'two_factor_app_enabled' => true,
        ]);
        User::factory()->create([
            'role' => 'cashier',
            'phone' => '0712345679',
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'));

        $response->assertOk()
            ->assertSee('Verified')
            ->assertSee('SMS · default')
            ->assertSee('App')
            ->assertSee('Unverified')
            ->assertSee('Off');
        $this->assertNotNull($verified->fresh()->phone_verified_at);
    }

    public function test_user_detail_page_shows_phone_verification_and_two_factor_methods(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->saveSmsSettings();
        $user = User::factory()->create([
            'role' => 'cashier',
            'phone' => '0712345678',
            'phone_verified_at' => now(),
            'two_factor_method' => 'sms',
            'two_factor_app_enabled' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('users.show', $user))
            ->assertOk()
            ->assertSee('Phone verification')
            ->assertSee('Verified')
            ->assertSee('SMS · default')
            ->assertSee('App');
    }

    public function test_changing_a_phone_number_resets_its_verification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'cashier',
            'phone' => '0712345678',
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->putJson(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '0712345679',
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_keeping_the_same_phone_number_preserves_its_verification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'role' => 'cashier',
            'phone' => '0712345678',
            'phone_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin)->putJson(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'phone' => '0712345678',
            'role' => 'cashier',
            'is_active' => true,
        ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertNotNull($user->fresh()->phone_verified_at);
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
