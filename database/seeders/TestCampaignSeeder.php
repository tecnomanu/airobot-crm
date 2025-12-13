<?php

namespace Database\Seeders;

use App\Enums\CampaignActionType;
use App\Enums\CampaignStatus;
use App\Enums\CampaignStrategy;
use App\Enums\LeadAutomationStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use App\Models\Lead\Lead;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeder for testing campaign flows
 * 
 * Creates two test campaigns:
 * 1. Direct Campaign - WhatsApp validation for all leads
 * 2. Dynamic Campaign - IVR with 2 options (WhatsApp or direct to sales_ready)
 */
class TestCampaignSeeder extends Seeder
{
    // Test phone number for all leads
    private const TEST_PHONE = '+5492944636430';
    
    // WhatsApp Wizard EvolutionAPI source ID
    private const WHATSAPP_SOURCE_ID = '019b14b4-ad14-7099-922d-8f5914899e8b';
    
    // Client ID (Acme Corporation)
    private const CLIENT_ID = '019b04f7-4671-714f-b0c2-3aa2ff654f3a';

    // Random names for leads
    private array $names = [
        'Carlos Mendoza',
        'María González',
        'Juan Pérez',
        'Ana Rodríguez',
        'Luis García',
        'Elena Martínez',
        'Pedro Sánchez',
        'Laura Fernández',
        'Roberto Díaz',
        'Sofía López',
        'Diego Torres',
        'Valentina Castro',
        'Miguel Herrera',
        'Camila Morales',
        'Andrés Vargas',
        'Isabella Romero',
        'Daniel Jiménez',
        'Lucía Ruiz',
        'Fernando Ortiz',
        'Paula Guerrero',
    ];

    public function run(): void
    {
        $this->command->info('Creating test campaigns and leads...');
        
        // Verify client exists
        $client = Client::find(self::CLIENT_ID);
        if (!$client) {
            $this->command->error('Client not found! Please run DatabaseSeeder first.');
            return;
        }

        // Create Campaign 1: Direct WhatsApp validation
        $campaign1 = $this->createDirectCampaign($client);
        $this->command->info("✓ Campaign 1 created: {$campaign1->name} (ID: {$campaign1->id})");

        // Create Campaign 2: Dynamic IVR with 2 options
        $campaign2 = $this->createDynamicCampaign($client);
        $this->command->info("✓ Campaign 2 created: {$campaign2->name} (ID: {$campaign2->id})");

        // Create 10 leads for Campaign 1
        $this->createLeadsForCampaign($campaign1, 10);
        $this->command->info("✓ Created 10 leads for Campaign 1");

        // Create 10 leads for Campaign 2 (5 with option 1, 5 with option 2)
        $this->createLeadsForDynamicCampaign($campaign2, 10);
        $this->command->info("✓ Created 10 leads for Campaign 2 (5 option 1, 5 option 2)");

        $this->command->info('');
        $this->command->info('=== Test Data Summary ===');
        $this->command->info("Campaign 1 (Direct): {$campaign1->id}");
        $this->command->info("Campaign 2 (Dynamic): {$campaign2->id}");
        $this->command->info("Test Phone: " . self::TEST_PHONE);
        $this->command->info("WhatsApp Source: " . self::WHATSAPP_SOURCE_ID);
    }

    /**
     * Create Campaign 1: Direct strategy
     * All leads trigger WhatsApp message for validation
     */
    private function createDirectCampaign(Client $client): Campaign
    {
        return Campaign::create([
            'name' => 'Test Direct - WhatsApp Validation',
            'client_id' => $client->id,
            'description' => 'Campaña de prueba DIRECTA: envía WhatsApp de validación a todos los leads',
            'status' => CampaignStatus::ACTIVE,
            'campaign_slug' => 'test-direct-whatsapp-' . Str::random(6),
            'auto_process_enabled' => true,
            'country' => 'AR',
            'strategy_type' => CampaignStrategy::DIRECT,
            'configuration' => [
                'trigger_action' => CampaignActionType::WHATSAPP->value,
                'source_id' => self::WHATSAPP_SOURCE_ID,
                'message' => 'Hola {{name}}! Gracias por tu interés. ¿Te gustaría recibir más información sobre nuestros servicios? Responde SÍ o NO.',
                'delay_seconds' => 0,
            ],
            'created_by' => 1,
        ]);
    }

    /**
     * Create Campaign 2: Dynamic strategy (IVR)
     * Option 1: Send WhatsApp
     * Option 2: Skip (direct to sales_ready)
     */
    private function createDynamicCampaign(Client $client): Campaign
    {
        return Campaign::create([
            'name' => 'Test Dynamic - IVR 2 Options',
            'client_id' => $client->id,
            'description' => 'Campaña de prueba DINÁMICA (IVR): Opción 1 = WhatsApp, Opción 2 = directo a Sales Ready',
            'status' => CampaignStatus::ACTIVE,
            'campaign_slug' => 'test-dynamic-ivr-' . Str::random(6),
            'auto_process_enabled' => true,
            'country' => 'AR',
            'strategy_type' => CampaignStrategy::DYNAMIC,
            'configuration' => [
                'fallback_action' => CampaignActionType::MANUAL_REVIEW->value,
                'mapping' => [
                    '1' => [
                        'action' => CampaignActionType::WHATSAPP->value,
                        'source_id' => self::WHATSAPP_SOURCE_ID,
                        'message' => 'Hola {{name}}! Has seleccionado la opción 1. Te contactaremos pronto para darte más información.',
                    ],
                    '2' => [
                        'action' => CampaignActionType::SKIP->value,
                        // Lead goes directly to sales_ready (validated immediately)
                    ],
                ],
            ],
            'created_by' => 1,
        ]);
    }

    /**
     * Create leads for a direct campaign
     * All leads start in inbox (automation_status = pending)
     */
    private function createLeadsForCampaign(Campaign $campaign, int $count): void
    {
        $shuffledNames = $this->names;
        shuffle($shuffledNames);

        for ($i = 0; $i < $count; $i++) {
            Lead::create([
                'phone' => self::TEST_PHONE,
                'name' => $shuffledNames[$i % count($shuffledNames)],
                'campaign_id' => $campaign->id,
                'status' => LeadStatus::PENDING,
                'source' => LeadSource::MANUAL,
                'automation_status' => LeadAutomationStatus::PENDING,
                'notes' => "Test lead #{$i} for direct campaign",
                'created_by' => 1,
            ]);
        }
    }

    /**
     * Create leads for a dynamic campaign with different options
     * 5 leads with option 1 (WhatsApp)
     * 5 leads with option 2 (direct to sales_ready)
     */
    private function createLeadsForDynamicCampaign(Campaign $campaign, int $count): void
    {
        $shuffledNames = $this->names;
        shuffle($shuffledNames);

        for ($i = 0; $i < $count; $i++) {
            $option = ($i < $count / 2) ? '1' : '2';
            
            Lead::create([
                'phone' => self::TEST_PHONE,
                'name' => $shuffledNames[$i % count($shuffledNames)],
                'campaign_id' => $campaign->id,
                'status' => LeadStatus::PENDING,
                'source' => LeadSource::WEBHOOK_INICIAL,
                'option_selected' => $option,
                'automation_status' => LeadAutomationStatus::PENDING,
                'notes' => "Test lead #{$i} for dynamic campaign (option: {$option})",
                'created_by' => 1,
            ]);
        }
    }
}

