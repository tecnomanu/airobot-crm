<?php

namespace App\Repositories\Interfaces;

use App\Models\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ClientRepositoryInterface
{
    /**
     * Obtener clientes paginados con filtros opcionales
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Buscar cliente por ID con relaciones
     */
    public function findById(string $id, array $with = []): ?Client;

    /**
     * Crear un nuevo cliente
     */
    public function create(array $data): Client;

    /**
     * Actualizar un cliente existente
     */
    public function update(Client $client, array $data): Client;

    /**
     * Eliminar un cliente
     */
    public function delete(Client $client): bool;

    /**
     * Obtener clientes activos
     */
    public function getActive(): Collection;

    /**
     * Buscar cliente por email
     */
    public function findByEmail(string $email): ?Client;

    /**
     * Obtener métricas de un cliente (campañas, leads, llamadas)
     */
    public function getMetrics(int $clientId): array;
}

