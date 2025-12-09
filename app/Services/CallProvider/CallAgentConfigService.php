<?php

namespace App\Services\CallProvider;

use App\Models\Integration\CallAgentDefaultConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar la configuración base de agentes
 * Permite tener una configuración por defecto que se aplica a todos los agentes
 * pero que puede ser sobrescrita por agente individual
 */
class CallAgentConfigService
{
    /**
     * Obtener la configuración base activa
     */
    public function getDefaultConfig(): ?CallAgentDefaultConfig
    {
        try {
            return CallAgentDefaultConfig::getActive();
        } catch (\Exception $e) {
            // Si la tabla no existe aún, retornar null
            // Esto puede pasar durante migraciones o en desarrollo
            return null;
        }
    }

    /**
     * Obtener configuración completa para un agente
     * Combina la configuración base con los overrides del agente
     *
     * @param  array  $agentData  Datos del agente desde Retell
     * @param  array  $overrides  Valores específicos del agente que sobrescriben la base
     * @return array Configuración completa
     */
    public function getAgentConfig(array $agentData = [], array $overrides = []): array
    {
        $defaultConfig = $this->getDefaultConfig();
        $baseConfig = $defaultConfig?->config ?? $this->getDefaultBaseConfig();

        // Combinar: base -> agentData -> overrides (prioridad creciente)
        $config = array_merge(
            $baseConfig,
            $agentData,
            $overrides
        );

        return $config;
    }

    /**
     * Crear o actualizar la configuración base
     */
    public function updateDefaultConfig(array $config): CallAgentDefaultConfig
    {
        return DB::transaction(function () use ($config) {
            // Desactivar todas las configuraciones existentes
            CallAgentDefaultConfig::where('is_active', true)->update(['is_active' => false]);

            // Crear o activar una nueva configuración
            $defaultConfig = CallAgentDefaultConfig::firstOrCreate(
                ['name' => 'Default Configuration'],
                ['config' => [], 'is_active' => true]
            );

            $defaultConfig->update([
                'config' => $config,
                'is_active' => true,
            ]);

            return $defaultConfig;
        });
    }

    /**
     * Obtener configuración base por defecto si no existe ninguna
     */
    private function getDefaultBaseConfig(): array
    {
        return [
            // Modelo LLM
            'llm_model' => 'gpt-4.1',
            'llm_temperature' => 0.7,
            'llm_high_priority' => false,

            // Voz
            'voice_speed' => 1.0,
            'voice_temperature' => 0.7,
            'volume' => 1.0,
            'responsiveness' => 1.0,
            'interruption_sensitivity' => 1.0,

            // Realtime Transcription Settings
            'stt_mode' => 'fast',
            'vocab_specialization' => 'general',
            'denoising_mode' => 'noise-cancellation',

            // Call Settings
            'end_call_after_silence_ms' => 600000, // 10 minutos
            'max_call_duration_ms' => 3600000, // 1 hora
            'ring_duration_ms' => 30000, // 30 segundos
            'begin_message_delay_ms' => 1000,

            // User DTMF
            'allow_user_dtmf' => true,
            'user_dtmf_options' => [
                'digit_limit' => 25,
                'termination_key' => '#',
                'timeout_ms' => 8000,
            ],

            // Funciones por defecto
            'functions' => [
                [
                    'type' => 'end_call',
                    'name' => 'end_call',
                    'description' => 'Terminar la llamada',
                ],
            ],

            // Webhook por defecto (lead intention)
            'webhook_url' => null, // Se configurará dinámicamente
            'webhook_timeout_ms' => 10000,
        ];
    }

    /**
     * Construir datos de agente para Retell API
     * Aplica configuración base + overrides específicos
     */
    public function buildAgentDataForRetell(array $overrides = []): array
    {
        $baseConfig = $this->getDefaultConfig()?->config ?? $this->getDefaultBaseConfig();

        // Combinar: base config -> overrides (los overrides tienen prioridad)
        $config = array_merge($baseConfig, array_filter($overrides, fn($v) => $v !== null));

        // Estructura requerida por Retell API
        $agentData = [
            'agent_name' => $overrides['agent_name'] ?? 'Agent',
            'voice_id' => $overrides['voice_id'] ?? null,
            'language' => $overrides['language'] ?? $config['language'] ?? 'es-ES',
            'voice_speed' => $config['voice_speed'] ?? 1.0,
            'voice_temperature' => $config['voice_temperature'] ?? 0.7,
        ];

        // Response Engine (Retell LLM)
        $agentData['response_engine'] = [
            'type' => 'retell-llm',
            'retell_llm' => [
                'model' => $config['llm_model'] ?? 'gpt-4.1',
                'model_temperature' => $config['llm_temperature'] ?? 0.7,
                'model_high_priority' => $config['llm_high_priority'] ?? false,
            ],
        ];

        // Primer mensaje si está configurado
        if (!empty($overrides['first_message'])) {
            $agentData['begin_message'] = $overrides['first_message'];
        } elseif (!empty($config['begin_message'])) {
            $agentData['begin_message'] = $config['begin_message'];
        }

        // Webhook si está configurado
        $webhookUrl = $overrides['webhook_url'] ?? $config['webhook_url'] ?? null;
        if (!empty($webhookUrl)) {
            $agentData['webhook_url'] = $webhookUrl;
            $agentData['webhook_timeout_ms'] = $config['webhook_timeout_ms'] ?? 10000;
        }

        // Call Settings
        $agentData['end_call_after_silence_ms'] = $config['end_call_after_silence_ms'] ?? 600000;
        $agentData['max_call_duration_ms'] = $config['max_call_duration_ms'] ?? 3600000;
        $agentData['ring_duration_ms'] = $config['ring_duration_ms'] ?? 30000;
        $agentData['begin_message_delay_ms'] = $config['begin_message_delay_ms'] ?? 1000;

        // Realtime Transcription Settings
        $agentData['stt_mode'] = $config['stt_mode'] ?? 'fast';
        $agentData['vocab_specialization'] = $config['vocab_specialization'] ?? 'general';
        $agentData['denoising_mode'] = $config['denoising_mode'] ?? 'noise-cancellation';

        // User DTMF
        $agentData['allow_user_dtmf'] = $config['allow_user_dtmf'] ?? true;
        if (!empty($config['user_dtmf_options'])) {
            $agentData['user_dtmf_options'] = $config['user_dtmf_options'];
        }

        return $agentData;
    }
}
