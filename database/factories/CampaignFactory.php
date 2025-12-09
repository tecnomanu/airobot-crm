<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state (solo datos básicos de campaña)
     * Los modelos relacionados (CallAgent, WhatsappAgent, Options) se crean con sus propias factories o callbacks
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'client_id' => Client::factory(),
            'description' => fake()->optional()->sentence(),
            'status' => fake()->randomElement(CampaignStatus::cases())->value,
            'match_pattern' => fake()->optional()->word(),
            'created_by' => User::factory(),
        ];
    }

    /**
     * Estado específico: activa
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::ACTIVE->value,
        ]);
    }

    /**
     * Estado específico: pausada
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::PAUSED->value,
        ]);
    }

    /**
     * Configurar con agentes y opciones completos
     */
    public function configure()
    {
        return $this->afterCreating(function ($campaign) {
            // Crear agente de llamadas
            $campaign->callAgent()->create([
                'name' => fake()->words(2, true),
                'provider' => 'vapi',
                'config' => [
                    'language' => 'es',
                    'voice' => 'female',
                    'script' => fake()->sentence(),
                    'max_duration' => 300,
                ],
                'enabled' => true,
            ]);

            // Crear agente de WhatsApp
            $campaign->whatsappAgent()->create([
                'name' => fake()->words(2, true),
                'source_id' => null, // Se puede asignar una fuente específica si existe
                'config' => [
                    'language' => 'es',
                    'tone' => 'friendly',
                    'rules' => ['Responder en menos de 5 minutos', 'Ser amable'],
                ],
                'enabled' => true,
            ]);

            // Crear las 4 opciones por defecto
            foreach (['1', '2', 'i', 't'] as $key) {
                $campaign->options()->create([
                    'option_key' => $key,
                    'action' => 'skip',
                    'source_id' => null,
                    'template_id' => null,
                    'message' => null,
                    'delay' => 5,
                    'enabled' => true,
                ]);
            }
        });
    }
}
