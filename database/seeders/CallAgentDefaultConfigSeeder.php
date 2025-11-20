<?php

namespace Database\Seeders;

use App\Models\CallAgentDefaultConfig;
use Illuminate\Database\Seeder;

class CallAgentDefaultConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear configuración base por defecto
        CallAgentDefaultConfig::updateOrCreate(
            ['name' => 'Default Configuration'],
            [
                'config' => [
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

                    // Idioma por defecto
                    'language' => 'es-ES',
                ],
                'is_active' => true,
            ]
        );
    }
}
