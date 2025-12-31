<?php

declare(strict_types=1);

namespace App\Events\Lead;

use App\Models\Lead\Lead;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Domain event emitted when a new lead is created.
 *
 * This is a domain event (not a broadcast event).
 * Listeners can react to perform side effects like:
 * - Broadcasting to frontend
 * - Triggering automation
 * - Sending notifications
 */
class LeadCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Lead $lead,
        public readonly ?string $source = null
    ) {}
}

