<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $client = Client::first();

        // Source WhatsApp (Evolution API)
        Source::create([
            'name' => 'WhatsApp Principal',
            'type' => 'whatsapp',
            'status' => 'active',
            'client_id' => $client?->id,
            'config' => [
                'api_url' => 'https://evolution.example.com',
                'api_key' => 'test-api-key-12345',
                'instance_name' => 'main-instance',
            ],
        ]);

        // Source Webhook HTTP
        Source::create([
            'name' => 'Webhook CRM Principal',
            'type' => 'webhook',
            'status' => 'active',
            'client_id' => $client?->id,
            'config' => [
                'url' => 'https://crm.example.com/api/leads',
                'method' => 'POST',
                'headers' => [
                    'Authorization' => 'Bearer token-12345',
                    'Content-Type' => 'application/json',
                ],
                'payload_template' => json_encode([
                    'lead_name' => '{{name}}',
                    'phone' => '{{phone}}',
                    'campaign' => '{{campaign}}',
                    'option_selected' => '{{option_selected}}',
                ], JSON_PRETTY_PRINT),
            ],
        ]);

        // Source Webhook Secundario
        Source::create([
            'name' => 'Webhook Analytics',
            'type' => 'webhook',
            'status' => 'active',
            'client_id' => null,
            'config' => [
                'url' => 'https://analytics.example.com/webhooks/leads',
                'method' => 'POST',
                'headers' => [
                    'X-API-Key' => 'analytics-key-67890',
                ],
                'payload_template' => json_encode([
                    'event' => 'lead_captured',
                    'data' => [
                        'name' => '{{name}}',
                        'phone' => '{{phone}}',
                    ],
                ], JSON_PRETTY_PRINT),
            ],
        ]);

        // Source WhatsApp Meta (activo)
        Source::create([
            'name' => 'WhatsApp Business Meta',
            'type' => 'meta_whatsapp',
            'status' => 'active',
            'client_id' => $client?->id,
            'config' => [
                'instance_name' => 'meta-instance',
                'api_url' => 'https://graph.facebook.com/v18.0',
                'api_key' => 'meta-token-placeholder-12345',
                'phone_number_id' => '123456789',
            ],
        ]);

        $this->command->info('✅ Fuentes de prueba creadas exitosamente');
    }
}
