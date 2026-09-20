<?php

namespace Tests\Feature;

use App\Models\Agent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashPointTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_cash_point_provisions_dmn_001_when_none_exist(): void
    {
        $this->assertSame(0, Agent::count());

        $agent = Agent::defaultCashPoint();

        $this->assertSame('DMN-001', $agent->code);
        $this->assertSame('active', $agent->status);
        $this->assertSame(1, $agent->newQuery()->count());
    }

    public function test_default_cash_point_returns_existing_agent_without_creating_duplicates(): void
    {
        $existing = Agent::create([
            'code' => 'DMN-001',
            'name' => 'Kilimani Cash Point',
            'phone' => '0712345678',
            'status' => 'active',
            'cash_balance' => 0,
        ]);

        $agent = Agent::defaultCashPoint();

        $this->assertSame($existing->id, $agent->id);
        $this->assertSame(1, Agent::count());
    }

    public function test_cash_point_helper_provisions_the_single_cash_point(): void
    {
        $this->assertSame(0, Agent::count());

        $this->assertInstanceOf(Agent::class, cash_point());
        $this->assertSame('DMN-001', cash_point()->code);
        $this->assertSame(1, Agent::count());
    }
}
