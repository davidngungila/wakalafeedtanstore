<?php

namespace Database\Factories;

use App\Models\User;
use App\Support\TwoFactor;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $secret = TwoFactor::generateSecret();

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_enabled' => true,
            'two_factor_secret' => Crypt::encryptString($secret),
            'two_factor_recovery_codes' => array_map(
                static fn (string $code): string => TwoFactor::hashRecoveryCode($code),
                TwoFactor::generateRecoveryCodes(),
            ),
        ];
    }

    /**
     * Indicate that the user should have two-factor authentication disabled.
     */
    public function withoutTwoFactor(): static
    {
        return $this->state(static fn (array $attributes): array => [
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
