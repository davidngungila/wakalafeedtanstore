<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Network;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 500, 1_500_000);

        return [
            'reference' => fake()->unique()->bothify('TXN-####-####'),
            'agent_id' => Agent::factory(),
            'network_id' => Network::factory(),
            'type' => fake()->randomElement(['deposit', 'withdrawal', 'send_money', 'bill_payment', 'airtime', 'bank_transfer']),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->unique()->numerify('07########'),
            'amount' => $amount,
            'fee' => fake()->randomFloat(2, 0, 500),
            'commission' => fake()->randomFloat(2, 0, 3_000),
            'status' => fake()->randomElement(['completed', 'completed', 'completed', 'pending', 'failed', 'reversed']),
            'provider_reference' => fake()->unique()->bothify('SRV-#########'),
        ];
    }
}
