<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ActiveSessionsTest extends TestCase
{
    use RefreshDatabase;

    public static function authorizedRoles(): array
    {
        return [
            'supervisor' => ['supervisor'],
            'admin' => ['admin'],
        ];
    }

    public static function unauthorizedRoles(): array
    {
        return [
            'cashier' => ['cashier'],
            'agent' => ['agent'],
        ];
    }

    #[DataProvider('authorizedRoles')]
    public function test_authorized_roles_can_view_active_sessions(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->createSession($user, [
            'ip_address' => '192.0.2.10',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124.0.0.0 Safari/537.36',
        ]);

        $this->actingAs($user)
            ->get(route('users.sessions'))
            ->assertOk()
            ->assertSee('Active Sessions')
            ->assertSee($user->name)
            ->assertSee('Chrome')
            ->assertSee('192.0.2.10');
    }

    #[DataProvider('unauthorizedRoles')]
    public function test_unauthorized_roles_cannot_view_active_sessions(string $role): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->createSession($user);

        $this->actingAs($user)
            ->get(route('users.sessions'))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('users.sessions'))
            ->assertRedirect(route('login'));
    }

    public function test_active_sessions_page_excludes_stale_sessions_and_marks_the_current_device(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $activeUser = User::factory()->create([
            'name' => 'Active User',
            'email' => 'active-user@example.com',
        ]);
        $staleUser = User::factory()->create([
            'name' => 'Stale User',
            'email' => 'stale-user@example.com',
        ]);

        $currentSessionId = Str::random(40);
        $this->withCookie((string) config('session.cookie'), $currentSessionId);
        $this->createSession($activeUser, [
            'id' => $currentSessionId,
            'ip_address' => '198.51.100.25',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1',
        ]);
        $this->createSession($staleUser, [
            'last_activity' => now()->subMinutes((int) config('session.lifetime', 120) + 1)->timestamp,
        ]);

        $this->actingAs($admin)
            ->get(route('users.sessions'))
            ->assertOk()
            ->assertSee('Active User')
            ->assertSee('iPhone')
            ->assertSee('198.51.100.25')
            ->assertSee('This device')
            ->assertDontSee('Stale User');
    }

    public function test_active_sessions_page_escapes_session_metadata(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'name' => '<script data-session-test="name">alert(1)</script>',
            'email' => 'session-escaping@example.com',
        ]);
        $this->createSession($user, [
            'user_agent' => '<svg data-session-test="agent">alert(1)</svg>',
        ]);

        $this->actingAs($admin)
            ->get(route('users.sessions'))
            ->assertOk()
            ->assertSee('&lt;script data-session-test=&quot;name&quot;&gt;', false)
            ->assertSee('&lt;svg data-session-test=&quot;agent&quot;&gt;', false)
            ->assertDontSee('<script data-session-test="name">alert(1)</script>', false)
            ->assertDontSee('<svg data-session-test="agent">alert(1)</svg>', false);
    }

    public function test_users_page_links_to_active_sessions(): void
    {
        $supervisor = User::factory()->create(['role' => 'supervisor']);

        $this->actingAs($supervisor)
            ->get(route('users.index'))
            ->assertOk()
            ->assertSee('View all active sessions')
            ->assertSee(route('users.sessions'), false);
    }

    private function createSession(User $user, array $attributes = []): void
    {
        DB::table('sessions')->insert(array_merge([
            'id' => Str::uuid()->toString(),
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Browser',
            'payload' => 'a:0:{}',
            'last_activity' => now()->timestamp,
        ], $attributes));
    }
}
