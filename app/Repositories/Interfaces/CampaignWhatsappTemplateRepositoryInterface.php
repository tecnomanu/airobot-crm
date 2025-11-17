<?php

namespace App\Repositories\Interfaces;

use App\Models\CampaignWhatsappTemplate;
use Illuminate\Database\Eloquent\Collection;

interface CampaignWhatsappTemplateRepositoryInterface
{
    public function findById(string $id): ?CampaignWhatsappTemplate;

    public function getByCampaign(string $campaignId): Collection;

    public function create(array $data): CampaignWhatsappTemplate;

    public function update(CampaignWhatsappTemplate $template, array $data): CampaignWhatsappTemplate;

    public function delete(CampaignWhatsappTemplate $template): bool;

    public function findByCampaignAndCode(string $campaignId, string $code): ?CampaignWhatsappTemplate;
}
