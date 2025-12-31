<?php

namespace Database\Seeders;

use App\Enums\CallAgentProvider;
use App\Enums\CallStatus;
use App\Enums\CampaignStatus;
use App\Enums\ClientStatus;
use App\Enums\LeadOptionSelected;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\MessageChannel;
use App\Enums\MessageDirection;
use App\Enums\MessageStatus;
use App\Enums\UserRole;
use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadCall;
use App\Models\Lead\LeadMessage;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create admin user (global, no client)
        $user = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@airobot.com',
            'password' => bcrypt('password'),
            'role' => UserRole::ADMIN,
            'is_seller' => false,
            'client_id' => null,
        ]);

        // Create client
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

        // Create sources
        $this->call(SourceSeeder::class);

        // Create campaign with related models
        $campaign = Campaign::create([
            'name' => 'Campaña Verano 2024',
            'client_id' => $client->id,
            'description' => 'Campaña de captación para el verano',
            'status' => CampaignStatus::ACTIVE,
            'match_pattern' => 'summer2024',
            'created_by' => $user->id,
        ]);

        // Create call agent
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

        // Create WhatsApp agent
        $campaign->whatsappAgent()->create([
            'name' => 'WhatsApp Bot',
            'source_id' => null,
            'config' => [
                'language' => 'es',
                'tone' => 'friendly',
                'rules' => ['Responder en menos de 5 minutos', 'Ser amable'],
            ],
            'enabled' => true,
        ]);

        // Create campaign options
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

        // Create leads
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

        // Create lead calls (new polymorphic model)
        LeadCall::create([
            'lead_id' => $lead1->id,
            'campaign_id' => $campaign->id,
            'phone' => '+34600111222',
            'call_date' => now()->subHours(3),
            'duration_seconds' => 180,
            'cost' => 0.15,
            'status' => CallStatus::COMPLETED,
            'provider' => 'vapi',
            'retell_call_id' => 'vapi_123456',
            'notes' => 'Llamada exitosa, cliente interesado',
            'recording_url' => 'https://example.com/recordings/123456.mp3',
            'transcript' => 'Agente: Hola, te llamo de Acme...\nCliente: Sí, estoy interesado...',
            'created_by' => $user->id,
        ]);

        LeadCall::create([
            'lead_id' => $lead2->id,
            'campaign_id' => $campaign->id,
            'phone' => '+34600333444',
            'call_date' => now()->subHours(1),
            'duration_seconds' => 0,
            'cost' => 0.05,
            'status' => CallStatus::NO_ANSWER,
            'provider' => 'vapi',
            'retell_call_id' => 'vapi_123457',
            'notes' => 'No contestó',
            'created_by' => $user->id,
        ]);

        // Create lead messages (new polymorphic model)
        LeadMessage::create([
            'lead_id' => $lead1->id,
            'campaign_id' => $campaign->id,
            'phone' => '+34600111222',
            'content' => 'Hola Juan! Gracias por tu interés en nuestros productos. ¿En qué podemos ayudarte?',
            'direction' => MessageDirection::OUTBOUND,
            'channel' => MessageChannel::WHATSAPP,
            'status' => MessageStatus::DELIVERED,
            'external_provider_id' => 'wa_msg_001',
            'created_by' => $user->id,
        ]);

        LeadMessage::create([
            'lead_id' => $lead1->id,
            'campaign_id' => $campaign->id,
            'phone' => '+34600111222',
            'content' => 'Hola! Me interesa el producto premium, ¿tienen alguna promoción?',
            'direction' => MessageDirection::INBOUND,
            'channel' => MessageChannel::WHATSAPP,
            'status' => MessageStatus::READ,
            'external_provider_id' => 'wa_msg_002',
        ]);

        LeadMessage::create([
            'lead_id' => $lead2->id,
            'campaign_id' => $campaign->id,
            'phone' => '+34600333444',
            'content' => 'Hola María! Te enviamos información sobre nuestros productos.',
            'direction' => MessageDirection::OUTBOUND,
            'channel' => MessageChannel::WHATSAPP,
            'status' => MessageStatus::SENT,
            'external_provider_id' => 'wa_msg_003',
            'created_by' => $user->id,
        ]);

        // Create WhatsApp templates
        $this->call(CampaignWhatsappTemplateSeeder::class);

        // Create additional users with different roles
        // Supervisor (global)
        User::factory()->create([
            'name' => 'Supervisor Global',
            'email' => 'supervisor@airobot.com',
            'password' => bcrypt('password'),
            'role' => UserRole::SUPERVISOR,
            'is_seller' => true, // Supervisors can also be sellers
            'client_id' => null,
        ]);

        // Seller for Acme Corporation
        User::factory()->create([
            'name' => 'Carlos Vendedor',
            'email' => 'carlos@acme.com',
            'password' => bcrypt('password'),
            'role' => UserRole::USER,
            'is_seller' => true,
            'client_id' => $client->id,
        ]);

        // Another seller for Acme Corporation
        User::factory()->create([
            'name' => 'Ana Ventas',
            'email' => 'ana@acme.com',
            'password' => bcrypt('password'),
            'role' => UserRole::USER,
            'is_seller' => true,
            'client_id' => $client->id,
        ]);

        // Regular user for Acme (not a seller)
        User::factory()->create([
            'name' => 'Pedro Soporte',
            'email' => 'pedro@acme.com',
            'password' => bcrypt('password'),
            'role' => UserRole::USER,
            'is_seller' => false,
            'client_id' => $client->id,
        ]);
    }
}
