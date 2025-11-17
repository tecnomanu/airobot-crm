<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Exceptions\Business\ValidationException;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Source;
use App\Models\User;
use App\Services\Campaign\CampaignService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test de integración entre Campaigns y Sources
 */
class CampaignSourceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected CampaignService $campaignService;

    protected Client $client;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->campaignService = app(CampaignService::class);
        $this->client = Client::factory()->create();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function puede_crear_campana_con_fuente_whatsapp()
    {
        $whatsappSource = Source::factory()->create([
            'type' => SourceType::WHATSAPP->value,
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $campaign = $this->campaignService->createCampaign([
            'name' => 'Campaña con WhatsApp Source',
            'client_id' => $this->client->id,
            'whatsapp_source_id' => $whatsappSource->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals($whatsappSource->id, $campaign->whatsapp_source_id);
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Campaña con WhatsApp Source',
            'whatsapp_source_id' => $whatsappSource->id,
        ]);
    }

    /** @test */
    public function puede_crear_campana_con_fuente_webhook()
    {
        $webhookSource = Source::factory()->create([
            'type' => SourceType::WEBHOOK->value,
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $campaign = $this->campaignService->createCampaign([
            'name' => 'Campaña con Webhook Source',
            'client_id' => $this->client->id,
            'webhook_source_id' => $webhookSource->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals($webhookSource->id, $campaign->webhook_source_id);
    }

    /** @test */
    public function puede_crear_campana_con_ambas_fuentes()
    {
        $whatsappSource = Source::factory()->create([
            'type' => SourceType::WHATSAPP->value,
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $webhookSource = Source::factory()->create([
            'type' => SourceType::WEBHOOK->value,
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $campaign = $this->campaignService->createCampaign([
            'name' => 'Campaña Completa',
            'client_id' => $this->client->id,
            'whatsapp_source_id' => $whatsappSource->id,
            'webhook_source_id' => $webhookSource->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals($whatsappSource->id, $campaign->whatsapp_source_id);
        $this->assertEquals($webhookSource->id, $campaign->webhook_source_id);
    }

    /** @test */
    public function no_puede_usar_fuente_whatsapp_inactiva()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('La fuente de WhatsApp debe estar activa');

        $whatsappSource = Source::factory()->create([
            'type' => SourceType::WHATSAPP->value,
            'status' => SourceStatus::INACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $this->campaignService->createCampaign([
            'name' => 'Campaña con WhatsApp inactivo',
            'client_id' => $this->client->id,
            'whatsapp_source_id' => $whatsappSource->id,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function no_puede_usar_fuente_webhook_tipo_incorrecto()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('no es de tipo Webhook');

        $whatsappSource = Source::factory()->create([
            'type' => SourceType::WHATSAPP->value,
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        // Intentar usar una fuente WhatsApp como webhook
        $this->campaignService->createCampaign([
            'name' => 'Campaña con tipo incorrecto',
            'client_id' => $this->client->id,
            'webhook_source_id' => $whatsappSource->id,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function no_puede_usar_fuente_no_mensajeria_como_whatsapp()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('no es de tipo WhatsApp');

        $webhookSource = Source::factory()->create([
            'type' => SourceType::WEBHOOK->value,
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        // Intentar usar un webhook como fuente de WhatsApp
        $this->campaignService->createCampaign([
            'name' => 'Campaña con tipo incorrecto',
            'client_id' => $this->client->id,
            'whatsapp_source_id' => $webhookSource->id,
            'created_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function puede_actualizar_fuente_whatsapp_de_campana()
    {
        $campaign = Campaign::factory()->create([
            'client_id' => $this->client->id,
            'whatsapp_source_id' => null,
        ]);

        $whatsappSource = Source::factory()->create([
            'type' => SourceType::WHATSAPP->value,
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $updated = $this->campaignService->updateCampaign($campaign->id, [
            'whatsapp_source_id' => $whatsappSource->id,
        ]);

        $this->assertEquals($whatsappSource->id, $updated->whatsapp_source_id);
    }

    /** @test */
    public function puede_cargar_relaciones_de_fuentes()
    {
        $whatsappSource = Source::factory()->create([
            'type' => SourceType::WHATSAPP->value,
            'status' => SourceStatus::ACTIVE->value,
            'name' => 'WhatsApp Ventas',
        ]);

        $webhookSource = Source::factory()->create([
            'type' => SourceType::WEBHOOK->value,
            'status' => SourceStatus::ACTIVE->value,
            'name' => 'Webhook CRM',
        ]);

        $campaign = Campaign::factory()->create([
            'whatsapp_source_id' => $whatsappSource->id,
            'webhook_source_id' => $webhookSource->id,
        ]);

        $campaign->load(['whatsappSource', 'webhookSource']);

        $this->assertNotNull($campaign->whatsappSource);
        $this->assertEquals('WhatsApp Ventas', $campaign->whatsappSource->name);
        $this->assertEquals(SourceType::WHATSAPP, $campaign->whatsappSource->type);

        $this->assertNotNull($campaign->webhookSource);
        $this->assertEquals('Webhook CRM', $campaign->webhookSource->name);
        $this->assertEquals(SourceType::WEBHOOK, $campaign->webhookSource->type);
    }

    /** @test */
    public function puede_reutilizar_misma_fuente_en_varias_campanas()
    {
        $whatsappSource = Source::factory()->create([
            'type' => SourceType::WHATSAPP->value,
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $campaign1 = $this->campaignService->createCampaign([
            'name' => 'Campaña 1',
            'client_id' => $this->client->id,
            'whatsapp_source_id' => $whatsappSource->id,
            'created_by' => $this->user->id,
        ]);

        $campaign2 = $this->campaignService->createCampaign([
            'name' => 'Campaña 2',
            'client_id' => $this->client->id,
            'whatsapp_source_id' => $whatsappSource->id,
            'created_by' => $this->user->id,
        ]);

        // Ambas campañas usan la misma fuente
        $this->assertEquals($whatsappSource->id, $campaign1->whatsapp_source_id);
        $this->assertEquals($whatsappSource->id, $campaign2->whatsapp_source_id);
        $this->assertEquals($campaign1->whatsapp_source_id, $campaign2->whatsapp_source_id);
    }

    /** @test */
    public function acepta_meta_whatsapp_como_fuente_valida()
    {
        $metaWhatsappSource = Source::factory()->create([
            'type' => SourceType::META_WHATSAPP->value,
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $campaign = $this->campaignService->createCampaign([
            'name' => 'Campaña con Meta WhatsApp',
            'client_id' => $this->client->id,
            'whatsapp_source_id' => $metaWhatsappSource->id,
            'created_by' => $this->user->id,
        ]);

        $this->assertEquals($metaWhatsappSource->id, $campaign->whatsapp_source_id);
    }
}
