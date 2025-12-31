<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\LeadAutomationStatus;
use App\Enums\LeadIntentionStatus;
use App\Enums\LeadStage;
use App\Enums\LeadStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Tests for LeadStage enum derivation logic.
 *
 * These tests verify that the stage is correctly computed from
 * the combination of status, automation_status, and intention_status.
 */
class LeadStageTest extends TestCase
{
    #[Test]
    public function it_returns_inbox_for_pending_lead_without_automation(): void
    {
        $stage = LeadStage::fromLead(
            status: LeadStatus::PENDING,
            automationStatus: null,
            intentionStatus: null,
            intention: null
        );

        $this->assertEquals(LeadStage::INBOX, $stage);
    }

    #[Test]
    public function it_returns_inbox_for_lead_with_pending_automation(): void
    {
        $stage = LeadStage::fromLead(
            status: LeadStatus::PENDING,
            automationStatus: LeadAutomationStatus::PENDING,
            intentionStatus: null,
            intention: null
        );

        $this->assertEquals(LeadStage::INBOX, $stage);
    }

    #[Test]
    public function it_returns_inbox_for_lead_with_failed_automation(): void
    {
        $stage = LeadStage::fromLead(
            status: LeadStatus::PENDING,
            automationStatus: LeadAutomationStatus::FAILED,
            intentionStatus: null,
            intention: null
        );

        $this->assertEquals(LeadStage::INBOX, $stage);
    }

    #[Test]
    public function it_returns_qualifying_for_lead_with_pending_intention(): void
    {
        $stage = LeadStage::fromLead(
            status: LeadStatus::IN_PROGRESS,
            automationStatus: LeadAutomationStatus::COMPLETED,
            intentionStatus: LeadIntentionStatus::PENDING,
            intention: null
        );

        $this->assertEquals(LeadStage::QUALIFYING, $stage);
    }

    #[Test]
    public function it_returns_qualifying_for_lead_with_processing_automation(): void
    {
        $stage = LeadStage::fromLead(
            status: LeadStatus::PENDING,
            automationStatus: LeadAutomationStatus::PROCESSING,
            intentionStatus: null,
            intention: null
        );

        $this->assertEquals(LeadStage::QUALIFYING, $stage);
    }

    #[Test]
    public function it_returns_sales_ready_for_interested_finalized_lead(): void
    {
        $stage = LeadStage::fromLead(
            status: LeadStatus::IN_PROGRESS,
            automationStatus: LeadAutomationStatus::COMPLETED,
            intentionStatus: LeadIntentionStatus::FINALIZED,
            intention: 'interested'
        );

        $this->assertEquals(LeadStage::SALES_READY, $stage);
    }

    #[Test]
    public function it_returns_sales_ready_for_sent_to_client_lead(): void
    {
        $stage = LeadStage::fromLead(
            status: LeadStatus::IN_PROGRESS,
            automationStatus: LeadAutomationStatus::COMPLETED,
            intentionStatus: LeadIntentionStatus::SENT_TO_CLIENT,
            intention: 'interested'
        );

        $this->assertEquals(LeadStage::SALES_READY, $stage);
    }

    #[Test]
    public function it_returns_not_interested_for_not_interested_finalized_lead(): void
    {
        $stage = LeadStage::fromLead(
            status: LeadStatus::IN_PROGRESS,
            automationStatus: LeadAutomationStatus::COMPLETED,
            intentionStatus: LeadIntentionStatus::FINALIZED,
            intention: 'not_interested'
        );

        $this->assertEquals(LeadStage::NOT_INTERESTED, $stage);
    }

    #[Test]
    public function it_returns_closed_for_closed_status(): void
    {
        // Closed status takes priority over everything else
        $stage = LeadStage::fromLead(
            status: LeadStatus::CLOSED,
            automationStatus: LeadAutomationStatus::COMPLETED,
            intentionStatus: LeadIntentionStatus::FINALIZED,
            intention: 'interested'
        );

        $this->assertEquals(LeadStage::CLOSED, $stage);
    }

    #[Test]
    public function it_returns_closed_for_invalid_status(): void
    {
        $stage = LeadStage::fromLead(
            status: LeadStatus::INVALID,
            automationStatus: null,
            intentionStatus: null,
            intention: null
        );

        $this->assertEquals(LeadStage::CLOSED, $stage);
    }

    #[Test]
    public function stage_has_correct_labels(): void
    {
        $this->assertEquals('Inbox', LeadStage::INBOX->label());
        $this->assertEquals('Calificando', LeadStage::QUALIFYING->label());
        $this->assertEquals('Listo para Ventas', LeadStage::SALES_READY->label());
        $this->assertEquals('No Interesado', LeadStage::NOT_INTERESTED->label());
        $this->assertEquals('Cerrado', LeadStage::CLOSED->label());
    }

    #[Test]
    public function stage_has_correct_colors(): void
    {
        $this->assertEquals('blue', LeadStage::INBOX->color());
        $this->assertEquals('yellow', LeadStage::QUALIFYING->color());
        $this->assertEquals('green', LeadStage::SALES_READY->color());
        $this->assertEquals('red', LeadStage::NOT_INTERESTED->color());
        $this->assertEquals('gray', LeadStage::CLOSED->color());
    }

    #[Test]
    public function tab_mapping_works_correctly(): void
    {
        $this->assertEquals(LeadStage::INBOX, LeadStage::fromTab('inbox'));
        $this->assertEquals(LeadStage::QUALIFYING, LeadStage::fromTab('active'));
        $this->assertEquals(LeadStage::SALES_READY, LeadStage::fromTab('sales_ready'));
        $this->assertEquals(LeadStage::CLOSED, LeadStage::fromTab('closed'));
        $this->assertEquals(LeadStage::INBOX, LeadStage::fromTab('errors'));
        $this->assertEquals(LeadStage::INBOX, LeadStage::fromTab('unknown'));
    }

    #[Test]
    public function stage_to_tab_mapping_works_correctly(): void
    {
        $this->assertEquals('inbox', LeadStage::INBOX->toTab());
        $this->assertEquals('active', LeadStage::QUALIFYING->toTab());
        $this->assertEquals('sales_ready', LeadStage::SALES_READY->toTab());
        $this->assertEquals('closed', LeadStage::NOT_INTERESTED->toTab());
        $this->assertEquals('closed', LeadStage::CLOSED->toTab());
    }

    #[Test]
    public function can_retry_automation_only_in_inbox(): void
    {
        $this->assertTrue(LeadStage::INBOX->canRetryAutomation());
        $this->assertFalse(LeadStage::QUALIFYING->canRetryAutomation());
        $this->assertFalse(LeadStage::SALES_READY->canRetryAutomation());
        $this->assertFalse(LeadStage::NOT_INTERESTED->canRetryAutomation());
        $this->assertFalse(LeadStage::CLOSED->canRetryAutomation());
    }

    #[Test]
    public function is_active_for_inbox_and_qualifying(): void
    {
        $this->assertTrue(LeadStage::INBOX->isActive());
        $this->assertTrue(LeadStage::QUALIFYING->isActive());
        $this->assertFalse(LeadStage::SALES_READY->isActive());
        $this->assertFalse(LeadStage::NOT_INTERESTED->isActive());
        $this->assertFalse(LeadStage::CLOSED->isActive());
    }

    #[Test]
    public function is_terminal_for_final_stages(): void
    {
        $this->assertFalse(LeadStage::INBOX->isTerminal());
        $this->assertFalse(LeadStage::QUALIFYING->isTerminal());
        $this->assertTrue(LeadStage::SALES_READY->isTerminal());
        $this->assertTrue(LeadStage::NOT_INTERESTED->isTerminal());
        $this->assertTrue(LeadStage::CLOSED->isTerminal());
    }

    #[Test]
    #[DataProvider('stageDerivationProvider')]
    public function it_derives_stage_correctly(
        ?LeadStatus $status,
        ?LeadAutomationStatus $automationStatus,
        ?LeadIntentionStatus $intentionStatus,
        ?string $intention,
        LeadStage $expectedStage
    ): void {
        $stage = LeadStage::fromLead($status, $automationStatus, $intentionStatus, $intention);
        $this->assertEquals($expectedStage, $stage);
    }

    public static function stageDerivationProvider(): array
    {
        return [
            'new_lead_no_fields' => [
                LeadStatus::PENDING,
                null,
                null,
                null,
                LeadStage::INBOX,
            ],
            'lead_with_skipped_automation' => [
                LeadStatus::PENDING,
                LeadAutomationStatus::SKIPPED,
                null,
                null,
                LeadStage::INBOX,
            ],
            'lead_in_progress_no_intention' => [
                LeadStatus::IN_PROGRESS,
                LeadAutomationStatus::COMPLETED,
                null,
                null,
                LeadStage::QUALIFYING,
            ],
            'contacted_lead_pending_intention' => [
                LeadStatus::CONTACTED,
                LeadAutomationStatus::COMPLETED,
                LeadIntentionStatus::PENDING,
                'some message',
                LeadStage::QUALIFYING,
            ],
            'qualified_lead_interested' => [
                LeadStatus::QUALIFIED,
                LeadAutomationStatus::COMPLETED,
                LeadIntentionStatus::FINALIZED,
                'interested',
                LeadStage::SALES_READY,
            ],
            'closed_overrides_interested' => [
                LeadStatus::CLOSED,
                LeadAutomationStatus::COMPLETED,
                LeadIntentionStatus::FINALIZED,
                'interested',
                LeadStage::CLOSED,
            ],
        ];
    }
}

