<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\PageToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_page_is_served_from_the_clean_path_without_a_page_parameter(): void
    {
        $user = $this->adminWithLogs(45);

        $this->actingAs($user)
            ->get('/audit?page=1')
            ->assertStatus(301)
            ->assertRedirect('/audit');

        // Filters are preserved, only the redundant page parameter is dropped.
        $this->actingAs($user)
            ->get('/audit?action=Probe&page=1')
            ->assertStatus(301)
            ->assertRedirect('/audit?action=Probe');

        $html = $this->actingAs($user)->get('/audit')->getContent();

        $this->assertStringNotContainsString('?page=1"', $html);
        $this->assertStringNotContainsString('?page=1&', $html);

        // A legacy plain page number redirects to its opaque token, so page
        // numbers never remain visible in the address bar.
        $this->actingAs($user)
            ->get('/audit?page=2')
            ->assertStatus(301)
            ->assertRedirect('/audit?page='.PageToken::encode(2));

        // Filters survive the rewrite.
        $this->actingAs($user)
            ->get('/audit?action=Probe&page=3')
            ->assertStatus(301)
            ->assertRedirect('/audit?action=Probe&page='.PageToken::encode(3));

        // The tokenized URL resolves without another redirect.
        $this->actingAs($user)
            ->get('/audit?page='.PageToken::encode(2))
            ->assertOk();
    }

    public function test_page_links_use_an_encrypted_token_and_it_resolves_to_the_right_page(): void
    {
        $user = $this->adminWithLogs(45);

        $html = $this->actingAs($user)->get('/audit')->getContent();

        // No plain page numbers leak into the links.
        $this->assertStringNotContainsString('?page=2', $html);
        $this->assertMatchesRegularExpression('/\?page=[A-Za-z0-9_-]{20,}/', $html);

        preg_match('/\?page=([A-Za-z0-9_-]+)/', $html, $matches);
        $token = $matches[1];

        $this->assertSame(2, PageToken::decode($token));
        $this->assertStringNotContainsString('page:2', $token);

        // The token link loads page 2 of the results.
        $this->actingAs($user)->get('/audit?page='.$token)->assertOk();
    }

    public function test_an_unrecognised_page_value_is_rejected(): void
    {
        $user = $this->adminWithLogs(45);

        $this->actingAs($user)
            ->get('/audit?page=GFASyuewskjjdskjdsbshjzxhjxzhjzxjhxzjxzjhjh')
            ->assertNotFound();
    }

    private function adminWithLogs(int $count): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= $count; $i++) {
            $log = AuditLog::create([
                'user_id' => $user->id,
                'action' => 'Probe '.$i,
                'entity_type' => 'Transaction',
                'entity_id' => $i,
                'details' => [],
            ]);
            $log->created_at = now()->subMinutes($i);
            $log->save();
        }

        return $user;
    }
}
