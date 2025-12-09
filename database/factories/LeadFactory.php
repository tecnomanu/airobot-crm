<?php

namespace Database\Factories;

use App\Enums\LeadAutomationStatus;
use App\Enums\LeadStatus;
use App\Models\Campaign\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'phone' => fake()->numerify('+521##########'),
            'name' => fake()->name(),
            'city' => fake()->city(),
            'option_selected' => null,
            'campaign_id' => Campaign::factory(),
            'status' => fake()->randomElement(LeadStatus::cases())->value,
            'source' => 'webhook',
            'sent_at' => now(),
            'intention' => fake()->optional()->sentence(),
            'notes' => fake()->optional()->sentence(),
            'tags' => [],
            'webhook_sent' => false,
            'webhook_result' => null,
            'automation_status' => LeadAutomationStatus::PENDING->value,
            'next_action_at' => null,
            'last_automation_run_at' => null,
            'automation_attempts' => 0,
            'automation_error' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * Estado específico: pendiente
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::PENDING->value,
        ]);
    }

    /**
     * Estado específico: calificado
     */
    public function qualified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LeadStatus::QUALIFIED->value,
        ]);
    }
}
