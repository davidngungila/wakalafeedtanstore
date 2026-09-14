<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashPointUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_old_field_names_no_longer_break_validation(): void
    {
        $agent = Agent::factory()->create([
            'code' => 'DMN-001',
            'name' => 'Kilimani Money Point',
            'phone' => '0712345678',
            'agent_level' => 'platinum',
            'status' => 'active',
        ]);

        $admin = User::factory()->create(['role' => 'admin', 'agent_id' => $agent->id]);

        // Simulate the live-site form: legacy field names handled by the
        // current validator.
        $response = $this->actingAs($admin)
            ->put(route('cash-point.update'), [
                'code' => 'DMN-001',
                'name' => 'Kilimani Money Point',
                'phone' => '0712345678',
                'agent_level' => 'platinum',
                'status' => 'active',
                'owner_name' => 'Baraka Mwenda',
                'national_id' => '19840514-12345-00001-23',
            ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('agents', [
            'code' => 'DMN-001',
            'owner_name' => 'Baraka Mwenda',
            'national_id' => '19840514-12345-00001-23',
        ]);
    }
}
