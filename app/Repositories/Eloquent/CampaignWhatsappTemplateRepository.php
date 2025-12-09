<?php

namespace App\Repositories\Eloquent;

use App\Models\Campaign\CampaignWhatsappTemplate;
use App\Repositories\Interfaces\CampaignWhatsappTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CampaignWhatsappTemplateRepository implements CampaignWhatsappTemplateRepositoryInterface
{
    public function findById(string $id): ?CampaignWhatsappTemplate
    {
        return CampaignWhatsappTemplate::find($id);
    }

    public function getByCampaign(string $campaignId): Collection
    {
        return CampaignWhatsappTemplate::where('campaign_id', $campaignId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): CampaignWhatsappTemplate
    {
        return CampaignWhatsappTemplate::create($data);
    }

    public function update(CampaignWhatsappTemplate $template, array $data): CampaignWhatsappTemplate
    {
        $template->update($data);

        return $template->fresh();
    }

    public function delete(CampaignWhatsappTemplate $template): bool
    {
        return $template->delete();
    }

    public function findByCampaignAndCode(string $campaignId, string $code): ?CampaignWhatsappTemplate
    {
        return CampaignWhatsappTemplate::where('campaign_id', $campaignId)
            ->where('code', $code)
            ->first();
    }
}
