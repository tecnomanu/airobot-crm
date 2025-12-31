<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CampaignStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use App\Models\Lead\Lead;
use App\Services\Lead\LeadIngestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests for lead ingestion from webhooks.
 */
class LeadIngestionTest extends TestCase
{
    use RefreshDatabase;

    private LeadIngestionService $ingestionService;

    private Campaign $campaign;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = Client::factory()->create();

        $this->campaign = Campaign::factory()->create([
            'client_id' => $this->client->id,
            'status' => CampaignStatus::ACTIVE,
            'slug' => 'test-campaign',
            'country' => 'AR',
        ]);

        $this->ingestionService = app(LeadIngestionService::class);
    }

    #[Test]
    public function it_creates_new_lead_from_webhook(): void
    {
        $leadData = [
            'phone' => '1155667788',
            'name' => 'John Doe',
            'city' => 'Buenos Aires',
            'campaign_id' => $this->campaign->id,
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertEquals('John Doe', $lead->name);
        $this->assertEquals('Buenos Aires', $lead->city);
        $this->assertEquals($this->campaign->id, $lead->campaign_id);
        $this->assertEquals(LeadStatus::PENDING, $lead->status);
        $this->assertEquals(LeadSource::WEBHOOK_INICIAL, $lead->source);
    }

    #[Test]
    public function it_normalizes_phone_with_campaign_country(): void
    {
        $leadData = [
            'phone' => '1155667788', // Without country code
            'name' => 'Test User',
            'campaign_id' => $this->campaign->id,
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        // Should be normalized to Argentine format: +549...
        $this->assertStringStartsWith('+549', $lead->phone);
    }

    #[Test]
    public function it_updates_existing_lead_instead_of_creating_duplicate(): void
    {
        $existingLead = Lead::factory()->create([
            'phone' => '+5491155667788',
            'campaign_id' => $this->campaign->id,
            'name' => 'Original Name',
        ]);

        $leadData = [
            'phone' => '1155667788',
            'name' => 'Updated Name',
            'city' => 'Córdoba',
            'campaign_id' => $this->campaign->id,
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        $this->assertEquals($existingLead->id, $lead->id);
        $this->assertEquals('Updated Name', $lead->name);
        $this->assertEquals('Córdoba', $lead->city);

        // Should not create a new lead
        $this->assertEquals(1, Lead::count());
    }

    #[Test]
    public function it_finds_campaign_by_slug(): void
    {
        $leadData = [
            'phone' => '1155667788',
            'name' => 'Test User',
            'slug' => 'test-campaign',
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        $this->assertEquals($this->campaign->id, $lead->campaign_id);
    }

    #[Test]
    public function it_finds_campaign_by_campaign_slug_alias(): void
    {
        $leadData = [
            'phone' => '1155667788',
            'name' => 'Test User',
            'campaign_slug' => 'test-campaign',
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        $this->assertEquals($this->campaign->id, $lead->campaign_id);
    }

    #[Test]
    public function it_finds_campaign_by_campaign_alias(): void
    {
        $leadData = [
            'phone' => '1155667788',
            'name' => 'Test User',
            'campaign' => 'test-campaign',
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        $this->assertEquals($this->campaign->id, $lead->campaign_id);
    }

    #[Test]
    public function it_falls_back_to_first_active_campaign(): void
    {
        // Create another active campaign
        $anotherCampaign = Campaign::factory()->create([
            'client_id' => $this->client->id,
            'status' => CampaignStatus::ACTIVE,
            'slug' => 'another-campaign',
        ]);

        $leadData = [
            'phone' => '1155667788',
            'name' => 'Test User',
            // No campaign identifier provided
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        // Should be assigned to one of the active campaigns
        $this->assertContains($lead->campaign_id, [
            $this->campaign->id,
            $anotherCampaign->id,
        ]);
    }

    #[Test]
    public function it_throws_exception_for_invalid_phone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number');

        $leadData = [
            'phone' => '123', // Too short to be valid
            'name' => 'Test User',
            'campaign_id' => $this->campaign->id,
        ];

        $this->ingestionService->processIncomingWebhookLead($leadData);
    }

    #[Test]
    public function it_throws_exception_when_no_active_campaign(): void
    {
        // Deactivate all campaigns
        Campaign::query()->update(['status' => CampaignStatus::PAUSED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Could not associate lead with any active campaign');

        $leadData = [
            'phone' => '1155667788',
            'name' => 'Test User',
        ];

        $this->ingestionService->processIncomingWebhookLead($leadData);
    }

    #[Test]
    public function it_preserves_option_selected(): void
    {
        $leadData = [
            'phone' => '1155667788',
            'name' => 'Test User',
            'option_selected' => '2',
            'campaign_id' => $this->campaign->id,
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        $this->assertEquals('2', $lead->option_selected);
    }

    #[Test]
    public function it_preserves_intention_if_provided(): void
    {
        $leadData = [
            'phone' => '1155667788',
            'name' => 'Test User',
            'intention' => 'interested',
            'campaign_id' => $this->campaign->id,
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        $this->assertEquals('interested', $lead->intention);
    }

    #[Test]
    public function it_uses_provided_source(): void
    {
        $leadData = [
            'phone' => '1155667788',
            'name' => 'Test User',
            'source' => LeadSource::WHATSAPP,
            'campaign_id' => $this->campaign->id,
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        $this->assertEquals(LeadSource::WHATSAPP, $lead->source);
    }

    #[Test]
    public function it_sets_client_id_from_campaign(): void
    {
        $leadData = [
            'phone' => '1155667788',
            'name' => 'Test User',
            'campaign_id' => $this->campaign->id,
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        $this->assertEquals($this->client->id, $lead->client_id);
    }

    #[Test]
    public function it_handles_phone_with_country_code(): void
    {
        $leadData = [
            'phone' => '+5491155667788',
            'name' => 'Test User',
            'campaign_id' => $this->campaign->id,
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        // Phone should remain normalized
        $this->assertEquals('+5491155667788', $lead->phone);
    }

    #[Test]
    public function it_handles_different_country_campaign(): void
    {
        $spanishCampaign = Campaign::factory()->create([
            'client_id' => $this->client->id,
            'status' => CampaignStatus::ACTIVE,
            'country' => 'ES',
        ]);

        $leadData = [
            'phone' => '612345678',
            'name' => 'Spanish User',
            'campaign_id' => $spanishCampaign->id,
        ];

        $lead = $this->ingestionService->processIncomingWebhookLead($leadData);

        // Should be normalized to Spanish format: +34...
        $this->assertStringStartsWith('+34', $lead->phone);
    }
}

