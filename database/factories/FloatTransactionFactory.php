<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\FloatTransaction;
use App\Models\Network;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FloatTransaction>
 */
class FloatTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => fake()->unique()->bothify('FLT-####-####'),
            'agent_id' => Agent::factory(),
            'network_id' => Network::factory(),
            'type' => fake()->randomElement(['cash_in', 'cash_out', 'float_topup', 'float_pull', 'cash_to_float']),
            'amount' => fake()->randomFloat(2, 10_000, 5_000_000),
            // Only float_topup books a network top-up fee; cash_to_float books a
            // commission. See App\Http\Controllers\FloatController.
            'fee' => fake()->boolean(50) ? 1500 : 0,
            'commission' => fake()->boolean(30) ? fake()->randomFloat(2, 100, 5_000) : 0,
            'status' => 'completed',
        ];
    }

    /**
     * A network float top-up, which is the float row that carries a fee.
     */
    public function topUp(): static
    {
        return $this->state(fn (): array => [
            'type' => 'float_topup',
            'fee' => 1500,
            'commission' => 0,
        ]);
    }

    /**
     * A cash-to-float conversion, which is the float row that carries a
     * commission.
     */
    public function cashToFloat(): static
    {
        return $this->state(fn (): array => [
            'type' => 'cash_to_float',
            'fee' => 0,
            'commission' => fake()->randomFloat(2, 100, 5_000),
        ]);
    }
}
