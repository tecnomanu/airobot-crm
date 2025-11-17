<?php

namespace App\Services\Lead;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Helpers\PhoneHelper;
use App\Models\Lead;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadService
{
    public function __construct(
        private LeadRepositoryInterface $leadRepository,
        private CampaignRepositoryInterface $campaignRepository,
        private ?LeadOptionProcessorService $optionProcessor = null
    ) {
        // Lazy load del servicio para evitar dependencias circulares
        if (! $this->optionProcessor) {
            $this->optionProcessor = app(LeadOptionProcessorService::class);
        }
    }

    /**
     * Obtener leads paginados con filtros
     */
    public function getLeads(array $filters = [], int $perPage = 15)
    {
        return $this->leadRepository->paginate($filters, $perPage);
    }

    /**
     * Obtener un lead por ID
     */
    public function getLeadById(string $id): ?Lead
    {
        return $this->leadRepository->findById($id, ['campaign.client', 'creator', 'callHistories']);
    }

    /**
     * Crear un nuevo lead
     * Valida que la campaña exista y esté activa
     */
    public function createLead(array $data): Lead
    {
        // Validar que la campaña exista
        $campaign = $this->campaignRepository->findById($data['campaign_id']);

        if (! $campaign) {
            throw new \InvalidArgumentException('Campaña no encontrada');
        }

        // Setear valores por defecto
        $data['status'] = $data['status'] ?? LeadStatus::PENDING;
        $data['webhook_sent'] = $data['webhook_sent'] ?? false;

        return $this->leadRepository->create($data);
    }

    /**
     * Actualizar un lead existente
     */
    public function updateLead(int $id, array $data): Lead
    {
        $lead = $this->leadRepository->findById($id);

        if (! $lead) {
            throw new \InvalidArgumentException('Lead no encontrado');
        }

        return $this->leadRepository->update($lead, $data);
    }

    /**
     * Eliminar un lead
     */
    public function deleteLead(int $id): bool
    {
        $lead = $this->leadRepository->findById($id);

        if (! $lead) {
            throw new \InvalidArgumentException('Lead no encontrado');
        }

        return $this->leadRepository->delete($lead);
    }

    /**
     * Asociar lead a campaña según slug
     * Busca la campaña que coincida con el slug y asigna el lead
     */
    public function assignToCampaignByPattern(string $pattern, array $leadData): ?Lead
    {
        $campaign = $this->campaignRepository->findByMatchPattern($pattern);

        if (! $campaign) {
            return null;
        }

        $leadData['campaign_id'] = $campaign->id;

        return $this->createLead($leadData);
    }

    /**
     * Marcar lead como enviado por webhook y registrar resultado
     */
    public function markWebhookSent(int $leadId, string $result): Lead
    {
        $lead = $this->leadRepository->findById($leadId);

        if (! $lead) {
            throw new \InvalidArgumentException('Lead no encontrado');
        }

        return $this->leadRepository->update($lead, [
            'webhook_sent' => true,
            'webhook_result' => $result,
            'sent_at' => now(),
        ]);
    }

    /**
     * Obtener leads pendientes de enviar por webhook
     */
    public function getPendingWebhookLeads()
    {
        return $this->leadRepository->getPendingWebhook();
    }

    /**
     * Obtener leads por campaña con estadísticas
     */
    public function getLeadsByCampaign(int $campaignId, ?string $status = null)
    {
        return $this->leadRepository->getByCampaignAndStatus($campaignId, $status);
    }

    /**
     * Obtener conteo de leads por estado para una campaña
     */
    public function getStatusCountByCampaign(int $campaignId): array
    {
        return $this->leadRepository->countByStatus($campaignId);
    }

    /**
     * Obtener leads recientes
     */
    public function getRecentLeads(int $limit = 10)
    {
        return $this->leadRepository->getRecent($limit);
    }

    /**
     * Procesar un lead entrante de webhook externo
     * Intenta asociarlo a una campaña y lo crea o actualiza
     */
    public function processIncomingWebhookLead(array $leadData): Lead
    {
        return DB::transaction(function () use ($leadData) {
            // Normalizar teléfono
            $phone = PhoneHelper::normalize($leadData['phone']);

            if (! PhoneHelper::isValid($phone)) {
                throw new \InvalidArgumentException('Número de teléfono inválido: '.$leadData['phone']);
            }

            // Buscar campaña
            $campaign = $this->findCampaignForLead($leadData);

            if (! $campaign) {
                Log::warning('No se encontró campaña para lead', [
                    'phone' => $phone,
                    'campaign_slug' => $leadData['slug'] ?? $leadData['campaign'] ?? $leadData['campaign_slug'] ?? null,
                ]);
                throw new \Exception('No se pudo asociar el lead a ninguna campaña activa.');
            }

            // Verificar si ya existe el lead (mismo teléfono + campaña)
            $existingLead = $this->leadRepository->findByPhoneAndCampaign($phone, $campaign->id);

            $data = [
                'phone' => $phone,
                'name' => $leadData['name'] ?? null,
                'city' => $leadData['city'] ?? null,
                'option_selected' => $leadData['option_selected'] ?? null,
                'campaign_id' => $campaign->id,
                'status' => $leadData['status'] ?? LeadStatus::PENDING,
                'source' => $leadData['source'] ?? LeadSource::WEBHOOK_INICIAL,
                'sent_at' => now(),
                'intention' => $leadData['intention'] ?? null,
                'notes' => $leadData['notes'] ?? null,
            ];

            if ($existingLead) {
                // Actualizar lead existente
                Log::info('Actualizando lead existente desde webhook', [
                    'lead_id' => $existingLead->id,
                    'phone' => $phone,
                ]);

                $lead = $this->leadRepository->update($existingLead, $data);
            } else {
                // Crear nuevo lead
                $data['webhook_sent'] = false;
                $data['webhook_result'] = null;
                $data['created_by'] = $leadData['created_by'] ?? null;

                Log::info('Creando nuevo lead desde webhook', [
                    'phone' => $phone,
                    'campaign_id' => $campaign->id,
                ]);

                $lead = $this->leadRepository->create($data);
            }

            // Procesamiento automático si está habilitado
            $this->autoProcessLeadIfEnabled($lead);

            return $lead;
        });
    }

    /**
     * Procesar automáticamente un lead si la campaña lo permite
     */
    protected function autoProcessLeadIfEnabled(Lead $lead): void
    {
        $campaign = $lead->campaign;

        // Verificar si el auto-proceso está habilitado
        if (! $campaign || ! $campaign->auto_process_enabled) {
            Log::info('Auto-proceso deshabilitado para esta campaña', [
                'lead_id' => $lead->id,
                'campaign_id' => $campaign?->id,
            ]);

            return;
        }

        // Verificar si el lead tiene una opción seleccionada
        if (! $lead->option_selected) {
            Log::info('Lead no tiene opción seleccionada, no se auto-procesa', [
                'lead_id' => $lead->id,
            ]);

            return;
        }

        // Buscar la configuración de la opción
        $option = $campaign->getOption($lead->option_selected);

        if (! $option) {
            Log::warning('No se encontró configuración para la opción seleccionada', [
                'lead_id' => $lead->id,
                'option_selected' => $lead->option_selected,
            ]);

            return;
        }

        // Verificar que la opción esté habilitada
        if (! $option->enabled) {
            Log::info('Opción deshabilitada, no se procesa', [
                'lead_id' => $lead->id,
                'option_key' => $option->option_key,
            ]);

            return;
        }

        // Ejecutar la acción según el tipo
        try {
            Log::info('Ejecutando auto-procesamiento de lead', [
                'lead_id' => $lead->id,
                'option_key' => $option->option_key,
                'action' => $option->action->value,
            ]);

            $this->optionProcessor->processLeadOption($lead, $option);

            Log::info('Auto-procesamiento completado exitosamente', [
                'lead_id' => $lead->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error en auto-procesamiento de lead', [
                'lead_id' => $lead->id,
                'option_key' => $option->option_key,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // No lanzar excepción para no bloquear la creación del lead
        }
    }

    /**
     * Buscar campaña para un lead basado en varios criterios
     */
    private function findCampaignForLead(array $leadData)
    {
        // 1. Intentar por campaign_id directo
        if (isset($leadData['campaign_id'])) {
            $campaign = $this->campaignRepository->find($leadData['campaign_id']);
            if ($campaign) {
                return $campaign;
            }
        }

        // 2. Intentar por slug (acepta: 'slug', 'campaign_slug' o 'campaign')
        $slug = $leadData['slug'] ?? $leadData['campaign_slug'] ?? $leadData['campaign'] ?? null;
        if ($slug) {
            $campaign = $this->campaignRepository->findBySlug($slug);
            if ($campaign) {
                return $campaign;
            }
        }

        // 3. Buscar primera campaña activa (fallback)
        $activeCampaigns = $this->campaignRepository->getActive();
        if ($activeCampaigns->isNotEmpty()) {
            return $activeCampaigns->first();
        }

        return null;
    }
}
