<?php

namespace App\Repositories\Interfaces;

use App\Models\Lead\LeadCall;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LeadCallRepositoryInterface
{
    /**
     * Get paginated call history with optional filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Find call by ID with relations
     */
    public function findById(string $id, array $with = []): ?LeadCall;

    /**
     * Create a new call record
     */
    public function create(array $data): LeadCall;

    /**
     * Update a call record
     */
    public function update(LeadCall $call, array $data): LeadCall;

    /**
     * Delete a call record
     */
    public function delete(LeadCall $call): bool;

    /**
     * Get calls by client and date range
     */
    public function getByClientAndDateRange(string $clientId, ?string $startDate = null, ?string $endDate = null): Collection;

    /**
     * Get calls by campaign
     */
    public function getByCampaign(string $campaignId): Collection;

    /**
     * Find call by external provider ID
     */
    public function findByExternalId(string $externalId): ?LeadCall;

    /**
     * Find call by Retell call ID
     */
    public function findByRetellCallId(string $retellCallId): ?LeadCall;

    /**
     * Calculate total cost by client
     */
    public function getTotalCostByClient(string $clientId): float;

    /**
     * Calculate total duration by campaign
     */
    public function getTotalDurationByCampaign(string $campaignId): int;

    /**
     * Count calls by status
     */
    public function countByStatus(?string $clientId = null): array;
}

