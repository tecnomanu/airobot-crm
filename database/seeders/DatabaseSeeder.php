<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\CallHistory;
use App\Enums\ClientStatus;
use App\Enums\CampaignStatus;
use App\Enums\CallAgentProvider;
use App\Enums\LeadStatus;
use App\Enums\LeadSource;
use App\Enums\LeadOptionSelected;
use App\Enums\CallStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario de prueba
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@airobot.com',
            'password' => bcrypt('password'),
        ]);

        // Crear cliente
        $client = Client::create([
            'name' => 'Acme Corporation',
            'email' => 'contact@acme.com',
            'phone' => '+34900123456',
            'company' => 'Acme Corp SA',
            'billing_info' => [
                'tax_id' => 'B12345678',
                'address' => 'Calle Principal 123',
                'city' => 'Madrid',
                'country' => 'España',
            ],
            'status' => ClientStatus::ACTIVE,
            'notes' => 'Cliente VIP desde 2024',
            'created_by' => $user->id,
        ]);

        // Crear fuentes
        $this->call(SourceSeeder::class);

        // Crear campaña con nueva estructura de modelos relacionados
        $campaign = Campaign::create([
            'name' => 'Campaña Verano 2024',
            'client_id' => $client->id,
            'description' => 'Campaña de captación para el verano',
            'status' => CampaignStatus::ACTIVE,
            'match_pattern' => 'summer2024',
            'created_by' => $user->id,
        ]);

        // Crear agente de llamadas
        $campaign->callAgent()->create([
            'name' => 'Agent Summer',
            'provider' => CallAgentProvider::VAPI,
            'config' => [
                'language' => 'es',
                'voice' => 'female',
                'script' => 'Hola, te llamo de Acme Corporation...',
                'max_duration' => 300,
            ],
            'enabled' => true,
        ]);

        // Crear agente de WhatsApp
        $campaign->whatsappAgent()->create([
            'name' => 'WhatsApp Bot',
            'source_id' => null, // Se puede vincular a una fuente creada en SourceSeeder
            'config' => [
                'language' => 'es',
                'tone' => 'friendly',
                'rules' => ['Responder en menos de 5 minutos', 'Ser amable'],
            ],
            'enabled' => true,
        ]);

        // Crear las 4 opciones de la campaña
        $campaign->options()->createMany([
            [
                'option_key' => '1',
                'action' => 'skip',
                'source_id' => null,
                'template_id' => null,
                'message' => null,
                'delay' => 5,
                'enabled' => true,
            ],
            [
                'option_key' => '2',
                'action' => 'skip',
                'source_id' => null,
                'template_id' => null,
                'message' => 'Te enviaremos la información por email',
                'delay' => 5,
                'enabled' => true,
            ],
            [
                'option_key' => 'i',
                'action' => 'skip',
                'source_id' => null,
                'template_id' => null,
                'message' => 'Te enviamos el catálogo',
                'delay' => 5,
                'enabled' => true,
            ],
            [
                'option_key' => 't',
                'action' => 'skip',
                'source_id' => null,
                'template_id' => null,
                'message' => null,
                'delay' => 5,
                'enabled' => true,
            ],
        ]);

        // Crear leads
        $lead1 = Lead::create([
            'phone' => '+34600111222',
            'name' => 'Juan Pérez',
            'city' => 'Barcelona',
            'option_selected' => LeadOptionSelected::OPTION_1,
            'campaign_id' => $campaign->id,
            'status' => LeadStatus::PENDING,
            'source' => LeadSource::WEBHOOK_INICIAL,
            'intention' => 'Interesado en el producto premium',
            'notes' => 'Contactar por la mañana',
            'webhook_sent' => false,
            'created_by' => $user->id,
        ]);

        $lead2 = Lead::create([
            'phone' => '+34600333444',
            'name' => 'María García',
            'city' => 'Valencia',
            'option_selected' => LeadOptionSelected::OPTION_2,
            'campaign_id' => $campaign->id,
            'status' => LeadStatus::CONTACTED,
            'source' => LeadSource::WHATSAPP,
            'sent_at' => now()->subDays(2),
            'intention' => 'Quiere más información',
            'webhook_sent' => true,
            'webhook_result' => 'Success',
            'created_by' => $user->id,
        ]);

        // Crear historial de llamadas
        CallHistory::create([
            'phone' => '+34600111222',
            'campaign_id' => $campaign->id,
            'client_id' => $client->id,
            'call_date' => now()->subHours(3),
            'duration_seconds' => 180,
            'cost' => 0.15,
            'status' => CallStatus::COMPLETED,
            'lead_id' => $lead1->id,
            'provider' => 'Vapi',
            'call_id_external' => 'vapi_123456',
            'notes' => 'Llamada exitosa, cliente interesado',
            'recording_url' => 'https://example.com/recordings/123456.mp3',
            'transcript' => 'Agente: Hola, te llamo de Acme...\nCliente: Sí, estoy interesado...',
            'created_by' => $user->id,
        ]);

        CallHistory::create([
            'phone' => '+34600333444',
            'campaign_id' => $campaign->id,
            'client_id' => $client->id,
            'call_date' => now()->subHours(1),
            'duration_seconds' => 0,
            'cost' => 0.05,
            'status' => CallStatus::NO_ANSWER,
            'lead_id' => $lead2->id,
            'provider' => 'Vapi',
            'call_id_external' => 'vapi_123457',
            'notes' => 'No contestó',
            'created_by' => $user->id,
        ]);

        // Crear plantillas de WhatsApp
        $this->call(CampaignWhatsappTemplateSeeder::class);
    }
}
