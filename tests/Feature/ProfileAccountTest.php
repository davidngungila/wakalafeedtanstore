<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileAccountTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'name' => 'Test Agent',
            'email' => 'test.agent@example.com',
            'phone' => '0712345678',
            'role' => 'agent',
            'is_active' => true,
        ], $overrides));
    }

    public function test_profile_page_renders_user_details_and_account_stats(): void
    {
        $agent = Agent::create([
            'code' => 'PRF-CPT',
            'name' => 'Feedtan Central',
            'phone' => '0712345678',
            'cash_balance' => 0,
        ]);

        $user = $this->user(['agent_id' => $agent->id]);

        $this->actingAs($user)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee('Test Agent')
            ->assertSee('test.agent@example.com')
            ->assertSee('Feedtan Central')
            ->assertSee('Active')
            ->assertSee('Edit profile')
            ->assertSee(route('profile.edit'))
            ->assertDontSee('Save profile')
            ->assertDontSee('Update password');
    }

    public function test_profile_edit_page_renders_profile_and_password_forms_before_two_factor_setup(): void
    {
        $user = $this->user([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Edit Profile')
            ->assertSee('Save profile')
            ->assertSee('Update password')
            ->assertSee("submitForm(form, { method: 'POST', done: () => setTimeout(() => location.reload(), 600) });", false);
    }

    public function test_account_page_renders_security_sections(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('Account & Security')
            ->assertSee('Two-factor authentication')
            ->assertSee('Active sessions')
            ->assertSee($user->name);
    }

    public function test_profile_page_without_agent_still_renders(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertSee('Linked cash point');
    }

    public function test_profile_update_changes_name_and_phone(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Renamed Agent',
                'email' => $user->email,
                'phone' => '0712345679',
            ], ['Accept' => 'application/json'])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Renamed Agent',
            'phone' => '0712345679',
        ]);
    }

    public function test_profile_update_uploads_avatar(): void
    {
        Storage::fake('public');

        $user = $this->user();

        $this->actingAs($user)
            ->post(route('profile.update'), [
                '_method' => 'PUT',
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => UploadedFile::fake()->image('photo.png', 100, 100),
            ], ['Accept' => 'application/json'])
            ->assertJson(['success' => true]);

        $this->assertNotNull($user->fresh()->profile_photo_path);

        Storage::disk('public')->assertExists($user->fresh()->profile_photo_path);

        $this->actingAs($user)
            ->get($user->fresh()->avatarUrl())
            ->assertOk();
    }
}
