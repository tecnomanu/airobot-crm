<?php

namespace Tests\Feature\Web;

use App\Models\Campaign\Campaign;
use App\Models\Lead\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function unauthenticated_user_cannot_access_leads()
    {
        $response = $this->get(route('leads.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function authenticated_user_can_list_leads()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(); // LeadFactory needs a campaign
        $leads = Lead::factory()->count(3)->create([
            'campaign_id' => $campaign->id
        ]);

        $response = $this->actingAs($user)->get(route('leads.index'));

        $response->assertStatus(200);
        // We verify that the page contains the leads (assuming React sends data as Inertia or JSON props)
        // Or at least that the request is successful.
    }

    #[Test]
    public function authenticated_user_can_create_lead()
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $leadData = [
            'name' => 'Jane Doe',
            'phone' => '+5215587654321',
            'campaign_id' => $campaign->id,
            'email' => 'jane@example.com',
            'notes' => 'Test lead',
        ];

        $response = $this->actingAs($user)
            ->withoutExceptionHandling()
            ->post(route('leads.store'), $leadData);

        // Assuming redirect after create
        $response->assertRedirect();
        
        $this->assertDatabaseHas('leads', [
            'phone' => '+5215587654321',
            'campaign_id' => $campaign->id,
        ]);
    }
}
