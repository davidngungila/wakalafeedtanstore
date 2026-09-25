<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_each_settings_page_without_the_internal_pane_navigation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pages = [
            ['settings.index', 'General Settings', 'Save general settings'],
            ['settings.commissions', 'Commission Settings', 'Save commission settings'],
            ['settings.security', 'Security Settings', 'Save security settings'],
            ['settings.notifications', 'Notification Settings', 'Save notification settings'],
            ['settings.cash-point', 'Cash Point Settings', 'Save cash point'],
            ['settings.email', 'Email Settings', 'Save email settings'],
        ];

        foreach ($pages as [$routeName, $title, $button]) {
            $this->actingAs($admin)
                ->get(route($routeName))
                ->assertOk()
                ->assertSee($title)
                ->assertSee($button)
                ->assertDontSee('settings-nav', false);
        }
    }

    public function test_cash_point_settings_form_uses_post_method_spoofing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings.cash-point'))
            ->assertOk()
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee("submitForm(form, { method: 'POST', done: () => toast('Cash point saved successfully.', 'success') });", false);
    }

    public function test_settings_sidebar_links_to_each_dedicated_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('settings.index'));

        $response->assertOk();
        $response->assertSee(route('settings.index'));
        $response->assertSee(route('settings.commissions'));
        $response->assertSee(route('settings.security'));
        $response->assertSee(route('settings.notifications'));
        $response->assertSee(route('settings.cash-point'));
        $response->assertSee(route('settings.email'));
    }

    public function test_layout_persists_sidebar_state_across_refreshes(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertSee('data-sidebar-state-key="sidebar-state-'.$admin->id.'"', false)
            ->assertSee('data-drop-key="finance"', false)
            ->assertSee('data-drop-key="system"', false)
            ->assertSee('localStorage.setItem', false)
            ->assertSee('beforeunload', false)
            ->assertSee('sidebarNav.scrollTop', false);
    }

    public function test_legacy_settings_pane_links_redirect_to_dedicated_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $legacyRoutes = [
            'general' => route('settings.index'),
            'commissions' => route('settings.commissions'),
            'security' => route('settings.security'),
            'notifications' => route('settings.notifications'),
            'cashpoint' => route('settings.cash-point'),
            'email' => route('settings.email'),
        ];

        foreach ($legacyRoutes as $pane => $destination) {
            $this->actingAs($admin)
                ->get(route('settings.index', ['pane' => $pane]))
                ->assertRedirect($destination);
        }

        $this->actingAs($admin)
            ->get(route('settings.index', ['pane' => 'unknown']))
            ->assertRedirect(route('settings.index'));
    }

    public function test_non_admin_cannot_open_settings_pages(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->get(route('settings.security'))
            ->assertForbidden();
    }
}
