<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogRenderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_page_renders_nested_detail_arrays_without_array_to_string_conversion(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'Reconciliation completed',
            'entity_type' => 'Reconciliation',
            'entity_id' => 42,
            'details' => [
                'before' => [
                    'status' => 'pending',
                ],
                'after' => [
                    'status' => 'completed',
                    'amount' => 8000,
                ],
                'reference' => 'TXN-001',
            ],
        ]);

        $this->actingAs($user)
            ->get(route('audit.index'))
            ->assertSee('<th>When</th>', false)
            ->assertSee('<th>User</th>', false)
            ->assertSee('<th>Action</th>', false)
            ->assertSee('<th>Entity</th>', false)
            ->assertDontSee('<th>Details</th>', false)
            ->assertDontSee('<th>IP</th>', false)
            ->assertSee('&quot;status&quot;:&quot;completed&quot;', false)
            ->assertSee('&quot;amount&quot;:8000', false)
            ->assertDontSee('Array to string conversion');
    }

    public function test_audit_page_paginates_twenty_logs_per_page_and_preserves_action_filter(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        for ($marker = 1; $marker <= 21; $marker++) {
            $this->createAuditLog($user, 'Pagination marker '.str_pad((string) $marker, 2, '0', STR_PAD_LEFT), $marker);
        }

        $cell = static fn (int $marker): string => '<div class="cell-title" style="font-size:12px;">#'.$marker.'</div>';

        $this->actingAs($user)
            ->get(route('audit.index', ['action' => 'Pagination marker']))
            ->assertSee('Page 1 of 2')
            ->assertSee($cell(1), false)
            ->assertSee($cell(20), false)
            ->assertDontSee($cell(21), false)
            ->assertSee('page=2', false)
            ->assertSee('action=Pagination%20marker', false);

        $this->actingAs($user)
            ->get(route('audit.index', ['action' => 'Pagination marker', 'page' => 2]))
            ->assertSee('Page 2 of 2')
            ->assertSee($cell(21), false)
            ->assertDontSee($cell(1), false);
    }

    private function createAuditLog(User $user, string $action, int $minutesAgo): AuditLog
    {
        $log = AuditLog::create([
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => 'Audit marker',
            'entity_id' => $minutesAgo,
            'details' => [],
        ]);
        $log->created_at = now()->subMinutes($minutesAgo);
        $log->save();

        return $log;
    }
}
