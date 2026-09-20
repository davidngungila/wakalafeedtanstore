<?php

namespace Tests;

use App\Models\Agent;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create the single cash point agent a fixture needs. The seeder no
     * longer ships one — tests create it explicitly.
     */
    protected function cashPoint(): Agent
    {
        return Agent::firstOrCreate(
            ['code' => 'DMN-001'],
            ['name' => 'Kilimani Money Point', 'phone' => '0712345678', 'agent_level' => 'platinum', 'status' => 'active', 'cash_balance' => 0],
        );
    }
}
