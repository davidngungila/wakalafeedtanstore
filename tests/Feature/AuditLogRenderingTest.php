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
            ->assertOk()
            ->assertSee('&quot;status&quot;:&quot;completed&quot;', false)
            ->assertSee('&quot;amount&quot;:8000', false)
            ->assertDontSee('Array to string conversion');
    }
}
