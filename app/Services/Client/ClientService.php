<?php

namespace App\Services\Client;

use App\Models\Client;
use App\Repositories\Interfaces\ClientRepositoryInterface;

class ClientService
{
    public function __construct(
        private ClientRepositoryInterface $clientRepository
    ) {}

    /**
     * Obtener clientes paginados con filtros
     */
    public function getClients(array $filters = [], int $perPage = 15)
    {
        return $this->clientRepository->paginate($filters, $perPage);
    }

    /**
     * Obtener un cliente por ID
     */
    public function getClientById(int $id): ?Client
    {
        return $this->clientRepository->findById($id, ['campaigns', 'creator']);
    }

    /**
     * Crear un nuevo cliente
     */
    public function createClient(array $data): Client
    {
        // Validar que el email sea único si se proporciona
        if (! empty($data['email'])) {
            $existing = $this->clientRepository->findByEmail($data['email']);
            if ($existing) {
                throw new \InvalidArgumentException('Ya existe un cliente con ese email');
            }
        }

        return $this->clientRepository->create($data);
    }

    /**
     * Actualizar un cliente existente
     */
    public function updateClient(int $id, array $data): Client
    {
        $client = $this->clientRepository->findById($id);

        if (! $client) {
            throw new \InvalidArgumentException('Cliente no encontrado');
        }

        // Validar email único si se está cambiando
        if (! empty($data['email']) && $data['email'] !== $client->email) {
            $existing = $this->clientRepository->findByEmail($data['email']);
            if ($existing) {
                throw new \InvalidArgumentException('Ya existe un cliente con ese email');
            }
        }

        return $this->clientRepository->update($client, $data);
    }

    /**
     * Eliminar un cliente
     * CUIDADO: Esto eliminará todas las campañas, leads y llamadas asociadas (cascade)
     */
    public function deleteClient(int $id): bool
    {
        $client = $this->clientRepository->findById($id);

        if (! $client) {
            throw new \InvalidArgumentException('Cliente no encontrado');
        }

        return $this->clientRepository->delete($client);
    }

    /**
     * Obtener clientes activos
     */
    public function getActiveClients()
    {
        return $this->clientRepository->getActive();
    }

    /**
     * Obtener métricas completas de un cliente
     * Retorna: total_campaigns, active_campaigns, total_leads, total_calls, total_cost
     */
    public function getClientMetrics(int $clientId): array
    {
        return $this->clientRepository->getMetrics($clientId);
    }

    /**
     * Activar o desactivar un cliente
     */
    public function toggleClientStatus(int $id): Client
    {
        $client = $this->clientRepository->findById($id);

        if (! $client) {
            throw new \InvalidArgumentException('Cliente no encontrado');
        }

        $newStatus = $client->status->value === 'active' ? 'inactive' : 'active';

        return $this->clientRepository->update($client, [
            'status' => $newStatus,
        ]);
    }
}
