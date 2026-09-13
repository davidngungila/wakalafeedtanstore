<?php

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('AG-####'),
            'name' => fake()->company(),
            'owner_name' => fake()->name(),
            'phone' => fake()->unique()->numerify('07########'),
            'national_id' => fake()->unique()->numerify('#############-#-#####-###'),
            'region' => fake()->city(),
            'district' => fake()->city(),
            'ward' => fake()->city(),
            'street' => fake()->streetName(),
            'agent_level' => fake()->randomElement(['bronze', 'silver', 'gold', 'platinum']),
            'status' => fake()->randomElement(['active', 'active', 'active', 'suspended']),
            'cash_balance' => fake()->randomFloat(2, 0, 50_000_000),
        ];
    }
}
