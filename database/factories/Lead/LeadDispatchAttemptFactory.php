<?php

namespace Database\Factories\Lead;

use App\Enums\DispatchStatus;
use App\Enums\DispatchTrigger;
use App\Enums\DispatchType;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadDispatchAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Lead\LeadDispatchAttempt>
 */
class LeadDispatchAttemptFactory extends Factory
{
    protected $model = LeadDispatchAttempt::class;

    public function definition(): array
    {
        return [
            'lead_id' => Lead::factory(),
            'client_id' => null,
            'campaign_id' => null,
            'type' => fake()->randomElement(DispatchType::cases())->value,
            'trigger' => fake()->randomElement(DispatchTrigger::cases())->value,
            'destination_id' => null,
            'request_payload' => json_encode(['test' => 'payload']),
            'request_url' => fake()->url(),
            'request_method' => 'POST',
            'response_status' => null,
            'response_body' => null,
            'status' => DispatchStatus::PENDING->value,
            'attempt_no' => 1,
            'next_retry_at' => null,
            'error_message' => null,
        ];
    }

    public function success(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DispatchStatus::SUCCESS->value,
            'response_status' => 200,
            'response_body' => json_encode(['success' => true]),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => DispatchStatus::FAILED->value,
            'response_status' => 500,
            'response_body' => 'Internal Server Error',
            'error_message' => 'Request failed with status 500',
        ]);
    }

    public function webhook(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DispatchType::WEBHOOK->value,
        ]);
    }

    public function googleSheet(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DispatchType::GOOGLE_SHEET->value,
        ]);
    }
}

