<?php

namespace App\Services\CallHistory;

use App\Models\CallHistory;
use App\Repositories\Interfaces\CallHistoryRepositoryInterface;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;

class CallHistoryService
{
    public function __construct(
        private CallHistoryRepositoryInterface $callHistoryRepository,
        private CampaignRepositoryInterface $campaignRepository,
        private LeadRepositoryInterface $leadRepository
    ) {}

    /**
     * Obtener historial paginado con filtros
     */
    public function getCallHistory(array $filters = [], int $perPage = 15)
    {
        return $this->callHistoryRepository->paginate($filters, $perPage);
    }

    /**
     * Obtener una llamada por ID
     */
    public function getCallById(string $id): ?CallHistory
    {
        return $this->callHistoryRepository->findById($id, ['campaign', 'client', 'lead', 'creator']);
    }

    /**
     * Registrar una llamada entrante (webhook desde proveedor de telefonía)
     * Vincula automáticamente con lead si se encuentra por teléfono
     */
    public function registerIncomingCall(array $data): CallHistory
    {
        // Buscar lead por teléfono si no se proporciona lead_id
        if (empty($data['lead_id']) && !empty($data['phone'])) {
            $lead = $this->leadRepository->findByPhone($data['phone']);
            if ($lead) {
                $data['lead_id'] = $lead->id;
                // Inferir campaña y cliente del lead si no se proporcionan
                $data['campaign_id'] = $data['campaign_id'] ?? $lead->campaign_id;
                $data['client_id'] = $data['client_id'] ?? $lead->campaign->client_id;
            }
        }

        // Validar que campaña y cliente existan
        if (empty($data['campaign_id']) || empty($data['client_id'])) {
            throw new \InvalidArgumentException('Se requiere campaign_id y client_id para registrar la llamada');
        }

        // Setear fecha de llamada si no se proporciona
        $data['call_date'] = $data['call_date'] ?? now();

        return $this->callHistoryRepository->create($data);
    }

    /**
     * Actualizar una llamada (ej: cuando termina, actualizar duración y transcript)
     */
    public function updateCall(string $id, array $data): CallHistory
    {
        $call = $this->callHistoryRepository->findById($id);

        if (!$call) {
            throw new \InvalidArgumentException('Llamada no encontrada');
        }

        return $this->callHistoryRepository->update($call, $data);
    }

    /**
     * Actualizar llamada por ID externo del proveedor
     */
    public function updateCallByExternalId(string $externalId, array $data): ?CallHistory
    {
        $call = $this->callHistoryRepository->findByExternalId($externalId);

        if (!$call) {
            return null;
        }

        return $this->callHistoryRepository->update($call, $data);
    }

    /**
     * Obtener llamadas de un cliente en un rango de fechas
     */
    public function getCallsByClientAndDateRange(string $clientId, ?string $startDate = null, ?string $endDate = null)
    {
        return $this->callHistoryRepository->getByClientAndDateRange($clientId, $startDate, $endDate);
    }

    /**
     * Obtener llamadas de una campaña
     */
    public function getCallsByCampaign(string $campaignId)
    {
        return $this->callHistoryRepository->getByCampaign($campaignId);
    }

    /**
     * Calcular costo total de llamadas de un cliente
     */
    public function getTotalCostByClient(string $clientId): float
    {
        return $this->callHistoryRepository->getTotalCostByClient($clientId);
    }

    /**
     * Calcular duración total de llamadas de una campaña (en segundos)
     */
    public function getTotalDurationByCampaign(string $campaignId): int
    {
        return $this->callHistoryRepository->getTotalDurationByCampaign($campaignId);
    }

    /**
     * Obtener conteo de llamadas por estado
     */
    public function getCallCountsByStatus(?string $clientId = null): array
    {
        return $this->callHistoryRepository->countByStatus($clientId);
    }
}
