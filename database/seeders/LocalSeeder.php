<?php

namespace Database\Seeders;

use App\Enums\CampaignStatus;
use App\Enums\CampaignStrategy;
use App\Enums\ClientStatus;
use App\Enums\LeadStage;
use App\Enums\LeadStatus;
use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Campaign\Campaign;
use App\Models\Campaign\CampaignAssignee;
use App\Models\Client\Client;
use App\Models\Integration\Source;
use App\Models\Lead\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Local Development Seeder - Creates sample data for development and testing.
 *
 * Includes:
 * - 1 Admin user
 * - 1 Client
 * - 2 Seller users
 * - 1 Multiple campaign (with assignees)
 * - 1 Direct campaign
 * - 1 Webhook source
 * - 1 WhatsApp Evolution API source
 * - 1 Demo lead (optional)
 *
 * Usage: php artisan db:seed --class=LocalSeeder
 */
class LocalSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🔧 Seeding local development environment...');
        $this->command->newLine();

        // ==============================
        // 1. CREATE ADMIN USER
        // ==============================
        $admin = User::create([
            'name' => 'Admin Root',
            'email' => 'admin@airobot.com',
            'password' => Hash::make('password'),
            'role' => UserRole::ADMIN,
            'status' => UserStatus::ACTIVE,
            'is_seller' => false,
            'client_id' => null,
        ]);
        $this->command->info("✅ Admin created: {$admin->email}");

        // ==============================
        // 2. CREATE CLIENT
        // ==============================
        $client = Client::create([
            'name' => 'Demo Client',
            'email' => 'demo@democlient.com',
            'phone' => '+5491122334455',
            'company' => 'Demo Company SA',
            'billing_info' => [
                'tax_id' => '30-12345678-9',
                'address' => 'Av. Corrientes 1234',
                'city' => 'Buenos Aires',
                'country' => 'Argentina',
            ],
            'status' => ClientStatus::ACTIVE,
            'notes' => 'Cliente de demostración para desarrollo local',
            'created_by' => $admin->id,
        ]);
        $this->command->info("✅ Client created: {$client->name}");

        // ==============================
        // 3. CREATE SELLER USERS
        // ==============================
        $seller1 = User::create([
            'name' => 'Juan Vendedor',
            'email' => 'juan@demo.com',
            'password' => Hash::make('password'),
            'role' => UserRole::USER,
            'status' => UserStatus::ACTIVE,
            'is_seller' => true,
            'client_id' => $client->id,
        ]);

        $seller2 = User::create([
            'name' => 'María Ventas',
            'email' => 'maria@demo.com',
            'password' => Hash::make('password'),
            'role' => UserRole::USER,
            'status' => UserStatus::ACTIVE,
            'is_seller' => true,
            'client_id' => $client->id,
        ]);
        $this->command->info("✅ Sellers created: {$seller1->email}, {$seller2->email}");

        // ==============================
        // 4. CREATE SOURCES
        // ==============================

        // WhatsApp Evolution API Source
        $whatsappSource = Source::create([
            'name' => 'LocalTesting',
            'type' => SourceType::WHATSAPP,
            'status' => SourceStatus::ACTIVE,
            'client_id' => $client->id,
            'config' => [
                'phone_number' => '+5491164169115',
                'provider' => 'evolution_api',
                'api_url' => 'https://evolution.incubit.com.ar',
                'instance_name' => 'LocalTesting',
                'api_key' => 'B7A8B257977A-4A81-92CF-971D4C520A5C',
            ],
        ]);
        $this->command->info("✅ WhatsApp Source created: {$whatsappSource->name}");

        // Webhook Source
        $webhookSource = Source::create([
            'name' => 'Webhook CRM Test',
            'type' => SourceType::WEBHOOK,
            'status' => SourceStatus::ACTIVE,
            'client_id' => $client->id,
            'config' => [
                'url' => 'https://webhook.site/test-endpoint',
                'method' => 'POST',
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-API-Key' => 'test-api-key',
                ],
            ],
        ]);
        $this->command->info("✅ Webhook Source created: {$webhookSource->name}");

        // ==============================
        // 5. CREATE CAMPAIGNS
        // ==============================

        // Campaign MULTIPLE (IVR style with options)
        $campaignMultiple = Campaign::create([
            'name' => 'Campaña Multiple Demo',
            'client_id' => $client->id,
            'description' => 'Campaña con múltiples opciones IVR',
            'status' => CampaignStatus::ACTIVE,
            'strategy_type' => CampaignStrategy::DYNAMIC,
            'match_pattern' => 'demo-multiple',
            'created_by' => $admin->id,
        ]);

        // Add options for multiple campaign
        $campaignMultiple->options()->createMany([
            [
                'option_key' => '1',
                'action' => 'whatsapp',
                'source_id' => $whatsappSource->id,
                'message' => '¡Gracias por elegir la opción 1!',
                'delay' => 5,
                'enabled' => true,
            ],
            [
                'option_key' => '2',
                'action' => 'whatsapp',
                'source_id' => $whatsappSource->id,
                'message' => 'Te enviamos información sobre la opción 2.',
                'delay' => 5,
                'enabled' => true,
            ],
        ]);

        // Assign sellers to multiple campaign
        CampaignAssignee::create([
            'campaign_id' => $campaignMultiple->id,
            'user_id' => $seller1->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        CampaignAssignee::create([
            'campaign_id' => $campaignMultiple->id,
            'user_id' => $seller2->id,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->command->info("✅ Multiple Campaign created: {$campaignMultiple->name} (with 2 assignees)");

        // Campaign DIRECT (simple, no options)
        $campaignDirect = Campaign::create([
            'name' => 'Campaña Directa Demo',
            'client_id' => $client->id,
            'description' => 'Campaña directa sin opciones',
            'status' => CampaignStatus::ACTIVE,
            'strategy_type' => CampaignStrategy::DIRECT,
            'match_pattern' => 'demo-direct',
            'created_by' => $admin->id,
        ]);

        // Assign one seller to direct campaign
        CampaignAssignee::create([
            'campaign_id' => $campaignDirect->id,
            'user_id' => $seller1->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->command->info("✅ Direct Campaign created: {$campaignDirect->name} (with 1 assignee)");

        // ==============================
        // 6. CREATE DEMO LEAD (optional)
        // ==============================
        $lead = Lead::create([
            'phone' => '+5491155667788',
            'name' => 'Lead de Prueba',
            'email' => 'lead@test.com',
            'city' => 'Buenos Aires',
            'country' => 'AR',
            'campaign_id' => $campaignMultiple->id,
            'stage' => LeadStage::INBOX,
            'status' => LeadStatus::PENDING,
            'source' => 'manual',
            'notes' => 'Lead creado para testing local',
            'created_by' => $admin->id,
        ]);
        $this->command->info("✅ Demo Lead created: {$lead->name} ({$lead->phone})");

        // ==============================
        // SUMMARY
        // ==============================
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════════');
        $this->command->info('   LOCAL DEVELOPMENT SEED COMPLETED');
        $this->command->info('═══════════════════════════════════════════');
        $this->command->newLine();
        $this->command->table(
            ['Type', 'Details'],
            [
                ['Admin', 'admin@airobot.com / password'],
                ['Seller 1', 'juan@demo.com / password'],
                ['Seller 2', 'maria@demo.com / password'],
                ['Client', $client->name],
                ['Multiple Campaign', $campaignMultiple->name],
                ['Direct Campaign', $campaignDirect->name],
                ['WhatsApp Source', $whatsappSource->name],
                ['Webhook Source', $webhookSource->name],
                ['Demo Lead', $lead->phone],
            ]
        );
        $this->command->newLine();
    }
}

