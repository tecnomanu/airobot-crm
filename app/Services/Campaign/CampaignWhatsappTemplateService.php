<?php

namespace App\Services\Campaign;

use App\Models\CampaignWhatsappTemplate;
use App\Repositories\Interfaces\CampaignWhatsappTemplateRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CampaignWhatsappTemplateService
{
    public function __construct(
        private CampaignWhatsappTemplateRepositoryInterface $templateRepository
    ) {}

    /**
     * Obtener todos los templates de una campaña
     */
    public function getTemplatesByCampaign(string $campaignId): Collection
    {
        return $this->templateRepository->getByCampaign($campaignId);
    }

    /**
     * Obtener un template por ID
     */
    public function getTemplateById(string $id): ?CampaignWhatsappTemplate
    {
        return $this->templateRepository->findById($id);
    }

    /**
     * Crear un nuevo template
     */
    public function createTemplate(array $data): CampaignWhatsappTemplate
    {
        // Validar que no exista otro template con el mismo código en la misma campaña
        if (isset($data['campaign_id']) && isset($data['code'])) {
            $existing = $this->templateRepository->findByCampaignAndCode(
                $data['campaign_id'],
                $data['code']
            );

            if ($existing) {
                throw new \InvalidArgumentException(
                    'Ya existe un template con ese código en esta campaña'
                );
            }
        }

        return $this->templateRepository->create($data);
    }

    /**
     * Actualizar un template existente
     */
    public function updateTemplate(string $id, array $data): CampaignWhatsappTemplate
    {
        $template = $this->templateRepository->findById($id);

        if (! $template) {
            throw new \InvalidArgumentException('Template no encontrado');
        }

        // Si se está cambiando el código, validar que no exista otro con el mismo código
        if (isset($data['code']) && $data['code'] !== $template->code) {
            $existing = $this->templateRepository->findByCampaignAndCode(
                $template->campaign_id,
                $data['code']
            );

            if ($existing) {
                throw new \InvalidArgumentException(
                    'Ya existe un template con ese código en esta campaña'
                );
            }
        }

        return $this->templateRepository->update($template, $data);
    }

    /**
     * Eliminar un template
     */
    public function deleteTemplate(string $id): bool
    {
        $template = $this->templateRepository->findById($id);

        if (! $template) {
            throw new \InvalidArgumentException('Template no encontrado');
        }

        return $this->templateRepository->delete($template);
    }
}
