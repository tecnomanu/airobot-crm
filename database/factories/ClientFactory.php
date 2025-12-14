<?php

namespace Database\Factories;

use App\Enums\ClientStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    protected $model = \App\Models\Client\Client::class;

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
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'billing_info' => [
                'address' => fake()->address(),
                'tax_id' => fake()->numerify('##-#######'),
            ],
            'status' => fake()->randomElement(ClientStatus::cases())->value,
            'notes' => fake()->optional()->sentence(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Estado específico: activo
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientStatus::ACTIVE->value,
        ]);
    }

    /**
     * Estado específico: inactivo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientStatus::INACTIVE->value,
        ]);
    }
}
