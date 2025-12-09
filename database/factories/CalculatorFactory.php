<?php

namespace Database\Factories;

use App\Models\Tool\Calculator;
use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalculatorFactory extends Factory
{
    protected $model = Calculator::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'client_id' => null,
            'name' => $this->faker->words(3, true),
            'data' => [],
            'last_cursor_position' => ['row' => 1, 'col' => 1],
            'column_widths' => [],
            'row_heights' => [],
            'frozen_rows' => 0,
            'frozen_columns' => 0,
        ];
    }

    public function withClient(): static
    {
        return $this->state(fn (array $attributes) => [
            'client_id' => Client::factory(),
        ]);
    }

    public function withData(array $data): static
    {
        return $this->state(fn (array $attributes) => [
            'data' => $data,
        ]);
    }
}
