<?php

namespace Tests\Feature\Web;

use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function unauthenticated_user_cannot_access_campaigns()
    {
        $response = $this->get(route('campaigns.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_list_campaigns()
    {
        $user = User::factory()->create();
        Campaign::factory()->count(3)->create();

        $response = $this->actingAs($user)->get(route('campaigns.index'));

        $response->assertStatus(200);
    }

    #[Test]
    public function authenticated_user_can_create_campaign()
    {
        $user = User::factory()->create();
        $client = Client::factory()->create();

        $campaignData = [
            'name' => 'New Sales Campaign',
            'client_id' => $client->id,
            'description' => 'Test description',
            'strategy_type' => 'dynamic',
        ];

        $response = $this->actingAs($user)->post(route('campaigns.store'), $campaignData);

        $response->assertRedirect(); // Assuming redirect

        $this->assertDatabaseHas('campaigns', [
            'name' => 'New Sales Campaign',
            'client_id' => $client->id,
        ]);
    }
}
