<?php

namespace Tests\Feature\Web;

use App\Enums\DispatchStatus;
use App\Enums\LeadAutomationStatus;
use App\Enums\LeadCloseReason;
use App\Enums\LeadStage;
use App\Enums\LeadStatus;
use App\Models\Campaign\Campaign;
use App\Models\Client\Client;
use App\Models\Lead\Lead;
use App\Models\Lead\LeadDispatchAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests for Lead Stage management, closing, automation, and dispatch functionality.
 *
 * Covers:
 * - Stage transitions (INBOX -> QUALIFYING -> SALES_READY -> CLOSED)
 * - Close/Reopen operations
 * - Automation start/pause
 * - Dispatch attempts and retry
 * - Invariants validation
 */
class LeadStageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->client = Client::factory()->create();
        $this->campaign = Campaign::factory()->create([
            'client_id' => $this->client->id,
        ]);
    }

    // ==========================================
    // 1. CLOSE LEAD TESTS
    // ==========================================

    #[Test]
    public function can_close_lead_with_reason_and_notes()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::INBOX,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.close', $lead->id), [
                'close_reason' => LeadCloseReason::INTERESTED->value,
                'close_notes' => 'Customer showed interest in premium plan',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $lead->refresh();

        // Invariant: stage=CLOSED => closed_at and close_reason present
        $this->assertEquals(LeadStage::CLOSED, $lead->stage);
        $this->assertNotNull($lead->closed_at);
        $this->assertEquals(LeadCloseReason::INTERESTED, $lead->close_reason);
        $this->assertEquals('Customer showed interest in premium plan', $lead->close_notes);
    }

    #[Test]
    public function cannot_close_already_closed_lead()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::CLOSED,
            'closed_at' => now(),
            'close_reason' => LeadCloseReason::NOT_INTERESTED,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.close', $lead->id), [
                'close_reason' => LeadCloseReason::INTERESTED->value,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function close_requires_valid_close_reason()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::INBOX,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.close', $lead->id), [
                'close_reason' => 'invalid_reason',
            ]);

        $response->assertSessionHasErrors('close_reason');
    }

    // ==========================================
    // 2. REOPEN LEAD TESTS
    // ==========================================

    #[Test]
    public function can_reopen_closed_lead()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::CLOSED,
            'status' => LeadStatus::CLOSED,
            'closed_at' => now()->subDay(),
            'close_reason' => LeadCloseReason::NOT_INTERESTED,
            'close_notes' => 'Previous close notes',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.reopen', $lead->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $lead->refresh();

        // Invariant: stage!=CLOSED => closed_at/close_reason null
        $this->assertEquals(LeadStage::INBOX, $lead->stage);
        $this->assertNull($lead->closed_at);
        $this->assertNull($lead->close_reason);
        $this->assertNull($lead->close_notes);
        $this->assertEquals(LeadAutomationStatus::PENDING, $lead->automation_status);
    }

    #[Test]
    public function cannot_reopen_non_closed_lead()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::QUALIFYING,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.reopen', $lead->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ==========================================
    // 3. STAGE TRANSITION TESTS
    // ==========================================

    #[Test]
    public function can_transition_stage_manually()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::INBOX,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.update-stage', $lead->id), [
                'stage' => LeadStage::QUALIFYING->value,
                'reason' => 'Manual qualification started',
            ]);

        $response->assertRedirect();

        $lead->refresh();
        $this->assertEquals(LeadStage::QUALIFYING, $lead->stage);
    }

    #[Test]
    public function cannot_transition_closed_lead_via_stage_endpoint()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::CLOSED,
            'closed_at' => now(),
            'close_reason' => LeadCloseReason::INTERESTED,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.update-stage', $lead->id), [
                'stage' => LeadStage::INBOX->value,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ==========================================
    // 4. SALES READY TESTS
    // ==========================================

    #[Test]
    public function can_mark_lead_as_sales_ready()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::QUALIFYING,
            'automation_status' => LeadAutomationStatus::RUNNING,
        ]);

        $seller = User::factory()->create(['is_seller' => true]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.sales-ready', $lead->id), [
                'assign_to_user_id' => $seller->id,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $lead->refresh();
        $this->assertEquals(LeadStage::SALES_READY, $lead->stage);
        $this->assertEquals($seller->id, $lead->assigned_to);
        $this->assertNotNull($lead->assigned_at);
        $this->assertEquals(LeadAutomationStatus::COMPLETED, $lead->automation_status);
    }

    // ==========================================
    // 5. AUTOMATION START/PAUSE TESTS
    // ==========================================

    #[Test]
    public function can_start_automation_from_inbox()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::INBOX,
            'automation_status' => LeadAutomationStatus::PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.automation.start', $lead->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $lead->refresh();
        $this->assertEquals(LeadStage::QUALIFYING, $lead->stage);
        $this->assertEquals(LeadAutomationStatus::RUNNING, $lead->automation_status);
    }

    #[Test]
    public function cannot_start_automation_from_sales_ready()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::SALES_READY,
            'automation_status' => LeadAutomationStatus::COMPLETED,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.automation.start', $lead->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $lead->refresh();
        // Stage should remain unchanged
        $this->assertEquals(LeadStage::SALES_READY, $lead->stage);
    }

    #[Test]
    public function cannot_start_automation_from_closed()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::CLOSED,
            'closed_at' => now(),
            'close_reason' => LeadCloseReason::INTERESTED,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.automation.start', $lead->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    #[Test]
    public function can_pause_running_automation()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::QUALIFYING,
            'automation_status' => LeadAutomationStatus::RUNNING,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('leads.automation.pause', $lead->id));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $lead->refresh();
        $this->assertEquals(LeadAutomationStatus::PAUSED, $lead->automation_status);
    }

    // ==========================================
    // 6. DISPATCH ATTEMPTS TESTS
    // ==========================================

    #[Test]
    public function can_list_dispatch_attempts_for_lead()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        LeadDispatchAttempt::factory()->count(3)->create([
            'lead_id' => $lead->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('leads.dispatch-attempts', $lead->id));

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    #[Test]
    public function can_retry_failed_dispatch_attempt()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $attempt = LeadDispatchAttempt::factory()->create([
            'lead_id' => $lead->id,
            'status' => DispatchStatus::FAILED,
            'attempt_no' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('dispatch-attempts.retry', $attempt->id));

        $response->assertRedirect();
    }

    #[Test]
    public function cannot_retry_successful_dispatch_attempt()
    {
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
        ]);

        $attempt = LeadDispatchAttempt::factory()->create([
            'lead_id' => $lead->id,
            'status' => DispatchStatus::SUCCESS,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('dispatch-attempts.retry', $attempt->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    // ==========================================
    // 7. INVARIANT TESTS
    // ==========================================

    #[Test]
    public function stage_is_source_of_truth_for_scopes()
    {
        // Create leads in different stages
        $inboxLead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::INBOX,
        ]);

        $qualifyingLead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::QUALIFYING,
        ]);

        $salesReadyLead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::SALES_READY,
        ]);

        $closedLead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::CLOSED,
            'closed_at' => now(),
            'close_reason' => LeadCloseReason::INTERESTED,
        ]);

        // Verify scopes work correctly
        $this->assertTrue(Lead::inbox()->where('id', $inboxLead->id)->exists());
        $this->assertFalse(Lead::inbox()->where('id', $qualifyingLead->id)->exists());

        $this->assertTrue(Lead::qualifying()->where('id', $qualifyingLead->id)->exists());
        $this->assertFalse(Lead::qualifying()->where('id', $inboxLead->id)->exists());

        $this->assertTrue(Lead::salesReady()->where('id', $salesReadyLead->id)->exists());
        $this->assertFalse(Lead::salesReady()->where('id', $closedLead->id)->exists());

        $this->assertTrue(Lead::closed()->where('id', $closedLead->id)->exists());
        $this->assertFalse(Lead::closed()->where('id', $inboxLead->id)->exists());

        $this->assertTrue(Lead::active()->where('id', $inboxLead->id)->exists());
        $this->assertFalse(Lead::active()->where('id', $closedLead->id)->exists());
    }

    #[Test]
    public function closed_lead_invariants_are_enforced()
    {
        // When closing a lead, all close fields must be set
        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::INBOX,
        ]);

        $this->actingAs($this->user)
            ->post(route('leads.close', $lead->id), [
                'close_reason' => LeadCloseReason::QUALIFIED->value,
                'close_notes' => 'Qualified for sales',
            ]);

        $lead->refresh();

        // Invariant check: CLOSED stage must have closed_at and close_reason
        $this->assertEquals(LeadStage::CLOSED, $lead->stage);
        $this->assertNotNull($lead->closed_at);
        $this->assertNotNull($lead->close_reason);

        // Invariant check: Non-CLOSED stage must have null close fields after reopen
        $this->actingAs($this->user)
            ->post(route('leads.reopen', $lead->id));

        $lead->refresh();

        $this->assertNotEquals(LeadStage::CLOSED, $lead->stage);
        $this->assertNull($lead->closed_at);
        $this->assertNull($lead->close_reason);
        $this->assertNull($lead->close_notes);
    }

    // ==========================================
    // 8. FULL FLOW TESTS
    // ==========================================

    #[Test]
    public function full_flow_inbox_to_closed_via_interested()
    {
        // Flow A: create lead -> INBOX -> close(INTERESTED) -> appears in Closed

        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::INBOX,
            'automation_status' => LeadAutomationStatus::PENDING,
        ]);

        // Verify starts in INBOX
        $this->assertEquals(LeadStage::INBOX, $lead->stage);
        $this->assertTrue(Lead::inbox()->where('id', $lead->id)->exists());

        // Close with INTERESTED
        $this->actingAs($this->user)
            ->post(route('leads.close', $lead->id), [
                'close_reason' => LeadCloseReason::INTERESTED->value,
            ]);

        $lead->refresh();

        // Verify appears in Closed
        $this->assertEquals(LeadStage::CLOSED, $lead->stage);
        $this->assertTrue(Lead::closed()->where('id', $lead->id)->exists());
        $this->assertFalse(Lead::inbox()->where('id', $lead->id)->exists());
    }

    #[Test]
    public function full_flow_close_not_interested_then_reopen()
    {
        // Flow B: close(NOT_INTERESTED) -> reopen -> back to INBOX

        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::QUALIFYING,
        ]);

        // Close with NOT_INTERESTED
        $this->actingAs($this->user)
            ->post(route('leads.close', $lead->id), [
                'close_reason' => LeadCloseReason::NOT_INTERESTED->value,
            ]);

        $lead->refresh();
        $this->assertEquals(LeadStage::CLOSED, $lead->stage);

        // Reopen
        $this->actingAs($this->user)
            ->post(route('leads.reopen', $lead->id));

        $lead->refresh();

        // Back to INBOX
        $this->assertEquals(LeadStage::INBOX, $lead->stage);
        $this->assertTrue(Lead::inbox()->where('id', $lead->id)->exists());
    }

    #[Test]
    public function full_flow_automation_qualifying_to_sales_ready()
    {
        // Flow C (partial): INBOX -> automation/start -> QUALIFYING -> sales-ready

        $lead = Lead::factory()->create([
            'campaign_id' => $this->campaign->id,
            'stage' => LeadStage::INBOX,
            'automation_status' => LeadAutomationStatus::PENDING,
        ]);

        // Start automation
        $this->actingAs($this->user)
            ->post(route('leads.automation.start', $lead->id));

        $lead->refresh();
        $this->assertEquals(LeadStage::QUALIFYING, $lead->stage);
        $this->assertEquals(LeadAutomationStatus::RUNNING, $lead->automation_status);

        // Mark sales ready
        $this->actingAs($this->user)
            ->post(route('leads.sales-ready', $lead->id));

        $lead->refresh();
        $this->assertEquals(LeadStage::SALES_READY, $lead->stage);
        $this->assertEquals(LeadAutomationStatus::COMPLETED, $lead->automation_status);

        // Verify in correct scope
        $this->assertTrue(Lead::salesReady()->where('id', $lead->id)->exists());
    }
}

