<?php

namespace Database\Factories;

use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 */
class JournalEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'entry_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'reference' => fake()->unique()->bothify('JE-####-####'),
            'description' => fake()->sentence(4),
            'status' => JournalEntry::STATUS_DRAFT,
            'created_by' => User::factory(),
            'posted_by' => null,
            'posted_at' => null,
        ];
    }

    /**
     * Mark the entry as posted.
     */
    public function posted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => JournalEntry::STATUS_POSTED,
            'posted_by' => $attributes['created_by'] ?? User::factory(),
            'posted_at' => now(),
        ]);
    }
}
