<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntryLine>
 */
class JournalEntryLineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'journal_entry_id' => JournalEntry::factory(),
            'account_id' => Account::factory(),
            'description' => fake()->sentence(3),
            'debit' => 0,
            'credit' => 0,
        ];
    }

    /**
     * Set the line as a debit.
     */
    public function debit(float $amount): static
    {
        return $this->state(fn (): array => [
            'debit' => $amount,
            'credit' => 0,
        ]);
    }

    /**
     * Set the line as a credit.
     */
    public function credit(float $amount): static
    {
        return $this->state(fn (): array => [
            'debit' => 0,
            'credit' => $amount,
        ]);
    }
}
