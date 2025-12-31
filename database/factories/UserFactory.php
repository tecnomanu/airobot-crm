<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
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
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => UserRole::USER,
            'status' => UserStatus::ACTIVE,
            'is_seller' => false,
            'client_id' => null,
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

    /**
     * Create an admin user.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::ADMIN,
        ]);
    }

    /**
     * Create a supervisor user.
     */
    public function supervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::SUPERVISOR,
        ]);
    }

    /**
     * Create a seller user.
     */
    public function seller(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_seller' => true,
        ]);
    }

    /**
     * Assign user to a client.
     */
    public function forClient(string $clientId): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => $clientId,
        ]);
    }

    /**
     * Create an inactive user.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::INACTIVE,
        ]);
    }
}
