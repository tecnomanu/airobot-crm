<?php

namespace Tests\Feature\Api;

use App\Models\Campaign\Campaign;
use App\Models\Lead\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function can_receive_lead_webhook_with_valid_token()
    {
        $token = 'test-token';
        Config::set('webhooks.token', $token);
        
        $campaign = Campaign::factory()->create();

        $payload = [
            'name' => 'John Doe',
            'phone' => '+5215512345678',
            'email' => 'john@example.com',
            'campaign_id' => $campaign->id,
            'source' => 'web_form',
        ];

        $response = $this->postJson(route('webhooks.lead'), $payload, [
            'X-Webhook-Token' => $token,
        ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('leads', [
            'phone' => '+5215512345678',
            'campaign_id' => $campaign->id,
        ]);
    }

    #[Test]
    public function cannot_receive_lead_webhook_with_invalid_token()
    {
        $token = 'test-token';
        Config::set('webhooks.token', $token);

        $response = $this->postJson(route('webhooks.lead'), [], [
            'X-Webhook-Token' => 'wrong-token',
        ]);

        $response->assertStatus(401);
    }

    #[Test]
    public function cannot_receive_lead_webhook_without_token()
    {
        Config::set('webhooks.token', 'secret');

        $response = $this->postJson(route('webhooks.lead'), []);

        $response->assertStatus(401);
    }
}
