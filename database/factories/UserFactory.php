<?php

namespace Database\Factories;

use App\Domains\Auth\Enums\AccountStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * A random per-process password; fixtures needing a known credential must set it explicitly.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make(Str::random(32)),
            'remember_token' => Str::random(10),
            'status' => AccountStatus::Active,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => null,
        ];
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

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountStatus::Suspended,
        ]);
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AccountStatus::Disabled,
        ]);
    }

    /**
     * @param  int  $minutes  positive minutes lock the account, negative minutes simulate an expired lock
     */
    public function locked(int $minutes = 15): static
    {
        return $this->state(fn (array $attributes) => [
            'locked_until' => now()->addMinutes($minutes),
        ]);
    }
}
