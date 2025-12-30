<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Enums\LeadManagerTab;
use App\Enums\LeadStage;
use App\Models\Lead\Lead;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service for read-only lead queries.
 *
 * Single Responsibility: Query and filter leads for UI display.
 * Does not modify data - use LeadService for writes.
 */
class LeadQueryService
{
    private const MANAGER_RELATIONS = ['campaign.client', 'creator', 'messages'];

    public function __construct(
        private readonly LeadRepositoryInterface $leadRepository
    ) {}

    /**
     * Get leads for the unified Leads Manager view with tab support.
     *
     * @param string $tab One of: 'inbox', 'active', 'sales_ready', 'closed', 'errors'
     * @param array $filters Additional filters (campaign_id, client_id, status, search)
     * @param int $perPage Pagination size
     */
    public function getLeadsForManager(string $tab, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Lead::query();

        $this->applyTabScope($query, $tab);
        $this->applyFilters($query, $filters);
        $this->applyEagerLoading($query);

        return $query->paginate($perPage);
    }

    /**
     * Get count summary for all tabs.
     */
    public function getTabCounts(array $filters = []): array
    {
        $baseQuery = Lead::query();

        $this->applyBaseFilters($baseQuery, $filters);

        return [
            'inbox' => (clone $baseQuery)->inbox()->count(),
            'active' => (clone $baseQuery)->activePipeline()->count(),
            'sales_ready' => (clone $baseQuery)->salesReady()->count(),
            'closed' => (clone $baseQuery)->closed()->count(),
            'errors' => (clone $baseQuery)->withErrors()->count(),
        ];
    }

    /**
     * Get paginated leads with basic filters.
     */
    public function getLeads(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->leadRepository->paginate($filters, $perPage);
    }

    /**
     * Get a lead by ID with optional relations.
     */
    public function getLeadById(string $id, array $with = []): ?Lead
    {
        $defaultRelations = ['campaign.client', 'creator', 'calls'];

        return $this->leadRepository->findById($id, array_merge($defaultRelations, $with));
    }

    /**
     * Get leads by campaign with optional status filter.
     */
    public function getLeadsByCampaign(string $campaignId, ?string $status = null)
    {
        return $this->leadRepository->getByCampaignAndStatus($campaignId, $status);
    }

    /**
     * Get status count breakdown for a campaign.
     */
    public function getStatusCountByCampaign(string $campaignId): array
    {
        return $this->leadRepository->countByStatus($campaignId);
    }

    /**
     * Get recent leads.
     */
    public function getRecentLeads(int $limit = 10)
    {
        return $this->leadRepository->getRecent($limit);
    }

    /**
     * Get leads pending webhook dispatch.
     */
    public function getPendingWebhookLeads()
    {
        return $this->leadRepository->getPendingWebhook();
    }

    /**
     * Get leads pending or failed automation.
     */
    public function getPendingAutomation(array $filters = []): LengthAwarePaginator
    {
        return $this->leadRepository->getPendingAutomation($filters);
    }

    /**
     * Get leads with failed automation for retry.
     */
    public function getFailedAutomation(array $filters = [])
    {
        return $this->leadRepository->getFailedAutomation($filters);
    }

    /**
     * Apply tab-specific scope to query.
     */
    private function applyTabScope(Builder $query, string $tab): void
    {
        $tabEnum = LeadManagerTab::tryFrom($tab) ?? LeadManagerTab::default();

        match ($tabEnum) {
            LeadManagerTab::INBOX => $query->inbox(),
            LeadManagerTab::ACTIVE => $query->activePipeline(),
            LeadManagerTab::SALES_READY => $query->salesReady(),
            LeadManagerTab::CLOSED => $query->closed(),
            LeadManagerTab::ERRORS => $query->withErrors(),
        };
    }

    /**
     * Apply all filters to query.
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $this->applyBaseFilters($query, $filters);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['stage'])) {
            $this->applyStageFilter($query, $filters['stage']);
        }
    }

    /**
     * Apply base filters (campaign_id, client_id) common to counts and list.
     */
    private function applyBaseFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['campaign_id'])) {
            $query->where('campaign_id', $filters['campaign_id']);
        }

        if (! empty($filters['client_id'])) {
            $query->forClient($filters['client_id']);
        }
    }

    /**
     * Apply stage filter by matching against derived stage.
     *
     * Note: Since stage is computed, we map it back to the underlying conditions.
     */
    private function applyStageFilter(Builder $query, string $stage): void
    {
        $leadStage = LeadStage::tryFrom($stage);

        if (! $leadStage) {
            return;
        }

        match ($leadStage) {
            LeadStage::INBOX => $query->inbox(),
            LeadStage::QUALIFYING => $query->activePipeline(),
            LeadStage::SALES_READY => $query->salesReady(),
            LeadStage::NOT_INTERESTED => $query->where('intention', 'not_interested')
                ->where('intention_status', 'finalized'),
            LeadStage::CLOSED => $query->closed(),
        };
    }

    /**
     * Apply eager loading for manager view.
     */
    private function applyEagerLoading(Builder $query): void
    {
        $query->with([
            'campaign.client',
            'creator',
            'assignee',
            'messages' => function ($q) {
                $q->latest()->limit(3);
            },
        ]);
    }
}
