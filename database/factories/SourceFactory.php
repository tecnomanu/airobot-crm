<?php

namespace Database\Factories;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Source>
 */
class SourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(SourceType::cases());

        return [
            'name' => fake()->words(3, true),
            'type' => $type->value,
            'config' => $this->getConfigForType($type),
            'status' => fake()->randomElement(SourceStatus::cases())->value,
            'client_id' => Client::factory(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Genera configuración válida según el tipo
     */
    protected function getConfigForType(SourceType $type): array
    {
        return match ($type) {
            SourceType::WHATSAPP => [
                'instance_name' => fake()->word(),
                'api_url' => fake()->url(),
                'api_key' => fake()->uuid(),
            ],
            SourceType::WEBHOOK => [
                'url' => fake()->url(),
                'method' => fake()->randomElement(['GET', 'POST', 'PUT']),
                'secret' => fake()->sha256(),
            ],
            SourceType::META_WHATSAPP => [
                'phone_number_id' => fake()->numerify('##########'),
                'access_token' => fake()->sha256(),
                'verify_token' => fake()->uuid(),
            ],
            SourceType::FACEBOOK_LEAD_ADS => [
                'page_id' => fake()->numerify('##########'),
                'access_token' => fake()->sha256(),
            ],
            SourceType::GOOGLE_ADS => [
                'customer_id' => fake()->numerify('##########'),
                'conversion_action_id' => fake()->numerify('##########'),
                'developer_token' => fake()->sha256(),
            ],
        };
    }

    /**
     * Estado específico: activo
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SourceStatus::ACTIVE->value,
        ]);
    }

    /**
     * Estado específico: inactivo
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SourceStatus::INACTIVE->value,
        ]);
    }

    /**
     * Tipo específico: WhatsApp
     */
    public function whatsapp(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SourceType::WHATSAPP->value,
            'config' => [
                'instance_name' => fake()->word(),
                'api_url' => fake()->url(),
                'api_key' => fake()->uuid(),
            ],
        ]);
    }

    /**
     * Tipo específico: Webhook
     */
    public function webhook(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => SourceType::WEBHOOK->value,
            'config' => [
                'url' => fake()->url(),
                'method' => 'POST',
                'secret' => fake()->sha256(),
            ],
        ]);
    }
}
