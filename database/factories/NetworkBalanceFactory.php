<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Network;
use App\Models\NetworkBalance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NetworkBalance>
 */
class NetworkBalanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'network_id' => Network::factory(),
            'opening_balance' => fake()->randomFloat(2, 100_000, 2_000_000),
            'balance' => fake()->randomFloat(2, 100_000, 2_000_000),
        ];
    }
}
