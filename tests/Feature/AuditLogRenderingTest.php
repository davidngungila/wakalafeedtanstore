<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\PageToken;
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

        $tokens = AuditLog::where('action', 'like', 'Pagination marker%')
            ->get()
            ->keyBy('entity_id')
            ->map(fn (AuditLog $log): string => $log->encrypted_entity_id);

        $cell = static fn (int $marker): string => '<div class="cell-title" style="font-size:12px;" title="'.$tokens[$marker].'">#';

        $response = $this->actingAs($user)
            ->get(route('audit.index', ['action' => 'Pagination marker']));

        $response
            ->assertSee('Showing <strong>1</strong> to <strong>20</strong>', false)
            ->assertSee('of <strong>21</strong> results', false)
            ->assertSee($cell(1), false)
            ->assertSee($cell(20), false)
            ->assertDontSee($cell(21), false)
            ->assertSee('page='.PageToken::encode(2), false)
            ->assertDontSee('page=2"', false)
            // The raw entity id is never rendered.
            ->assertDontSee('title="1"', false);

        // The action filter is preserved on the pager link.
        $this->assertMatchesRegularExpression(
            '/audit\?action=Pagination(\+|%20)marker&amp;page=/',
            $response->getContent()
        );

        $this->actingAs($user)
            ->get(route('audit.index', ['action' => 'Pagination marker', 'page' => PageToken::encode(2)]))
            ->assertSee('Showing <strong>21</strong> to <strong>21</strong>', false)
            ->assertSee($cell(21), false)
            ->assertDontSee($cell(1), false);
    }

    public function test_audit_page_never_renders_a_plain_entity_id(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $log = $this->createAuditLog($user, 'Plain id probe', 7);
        $log->update(['entity_type' => 'Transaction', 'entity_id' => 163]);

        $response = $this->actingAs($user)->get(route('audit.index'));

        $response->assertOk();
        // The raw id must not appear anywhere in the markup.
        $response->assertDontSee('>#163<', false);
        $response->assertDontSee('data-entityid="163"', false);
        $response->assertDontSee('title="163"', false);
        // The opaque token is used instead.
        $response->assertSee($log->encrypted_entity_id, false);
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
