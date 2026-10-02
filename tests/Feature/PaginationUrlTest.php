<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_page_is_served_from_the_clean_path_without_a_page_parameter(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 45; $i++) {
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
        $this->assertStringContainsString('page=2', $html);

        $this->actingAs($user)->get('/audit?page=2')->assertOk();
    }
}
