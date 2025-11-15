<?php

declare(strict_types=1);

namespace App\Services\Source;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Exceptions\Business\ValidationException;
use App\Models\Source;
use App\Repositories\Interfaces\SourceRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la lógica de negocio de Sources
 */
class SourceService
{
    /**
     * Constructor con inyección de dependencias
     */
    public function __construct(
        protected SourceRepositoryInterface $sourceRepository
    ) {}

    /**
     * Lista fuentes con paginación y filtros
     * 
     * @param array $filters [type, status, client_id, search, active_only]
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->sourceRepository->paginate($filters, $perPage);
    }

    /**
     * Obtiene todas las fuentes sin paginación
     * 
     * @param array $filters
     * @return Collection
     */
    public function getAll(array $filters = []): Collection
    {
        return $this->sourceRepository->getAll($filters);
    }

    /**
     * Obtiene una fuente por ID
     * 
     * @param int $id
     * @return Source
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getById(int $id): Source
    {
        return $this->sourceRepository->findOrFail($id);
    }

    /**
     * Crea una nueva fuente
     * 
     * @param array $data
     * @return Source
     * @throws ValidationException
     */
    public function create(array $data): Source
    {
        // Validar tipo válido
        $type = $this->validateAndGetType($data['type']);

        // Validar nombre único para el cliente
        $this->validateUniqueName($data['name'], $data['client_id'] ?? null);

        // Validar configuración según tipo
        $this->validateConfig($type, $data['config'] ?? []);

        // Establecer estado inicial si no viene
        if (!isset($data['status'])) {
            $data['status'] = SourceStatus::PENDING_SETUP->value;
        }

        return DB::transaction(function () use ($data) {
            $source = $this->sourceRepository->create($data);

            Log::info('Source created', [
                'source_id' => $source->id,
                'type' => $source->type->value,
                'client_id' => $source->client_id,
            ]);

            return $source;
        });
    }

    /**
     * Actualiza una fuente existente
     * 
     * @param int $id
     * @param array $data
     * @return Source
     * @throws ValidationException
     */
    public function update(int $id, array $data): Source
    {
        $source = $this->sourceRepository->findOrFail($id);

        // Si cambia el tipo, validarlo
        if (isset($data['type'])) {
            $type = $this->validateAndGetType($data['type']);
            
            // Si cambia el tipo, validar nueva config
            if ($source->type->value !== $type->value && isset($data['config'])) {
                $this->validateConfig($type, $data['config']);
            }
        }

        // Si cambia el nombre, validar unicidad
        if (isset($data['name']) && $data['name'] !== $source->name) {
            $this->validateUniqueName(
                $data['name'],
                $data['client_id'] ?? $source->client_id,
                $id
            );
        }

        // Si actualiza config, validar según tipo actual o nuevo
        if (isset($data['config'])) {
            $typeForValidation = isset($data['type']) 
                ? $this->validateAndGetType($data['type']) 
                : $source->type;
            $this->validateConfig($typeForValidation, $data['config']);
        }

        return DB::transaction(function () use ($id, $data, $source) {
            $updated = $this->sourceRepository->update($id, $data);

            Log::info('Source updated', [
                'source_id' => $id,
                'changes' => array_keys($data),
            ]);

            return $updated;
        });
    }

    /**
     * Elimina una fuente
     * 
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $source = $this->sourceRepository->findOrFail($id);

        // TODO: Validar que no tenga campañas activas asociadas cuando se implemente

        $deleted = $this->sourceRepository->delete($id);

        if ($deleted) {
            Log::info('Source deleted', [
                'source_id' => $id,
                'type' => $source->type->value,
            ]);
        }

        return $deleted;
    }

    /**
     * Activa una fuente
     * 
     * @param int $id
     * @return Source
     * @throws ValidationException
     */
    public function activate(int $id): Source
    {
        $source = $this->sourceRepository->findOrFail($id);

        // Validar que tenga configuración válida antes de activar
        if (!$source->hasValidConfig()) {
            throw new ValidationException(
                'No se puede activar la fuente sin una configuración válida'
            );
        }

        return $this->updateStatus($id, SourceStatus::ACTIVE);
    }

    /**
     * Desactiva una fuente
     * 
     * @param int $id
     * @return Source
     */
    public function deactivate(int $id): Source
    {
        return $this->updateStatus($id, SourceStatus::INACTIVE);
    }

    /**
     * Marca una fuente con error
     * 
     * @param int $id
     * @param string|null $errorMessage
     * @return Source
     */
    public function markAsError(int $id, ?string $errorMessage = null): Source
    {
        $source = $this->updateStatus($id, SourceStatus::ERROR);

        if ($errorMessage) {
            Log::error('Source marked as error', [
                'source_id' => $id,
                'error' => $errorMessage,
            ]);
        }

        return $source;
    }

    /**
     * Obtiene fuentes por tipo
     * 
     * @param SourceType|string $type
     * @param bool $activeOnly
     * @return Collection
     */
    public function getByType(SourceType|string $type, bool $activeOnly = false): Collection
    {
        return $activeOnly
            ? $this->sourceRepository->findActiveByType($type)
            : $this->sourceRepository->findByType($type);
    }

    /**
     * Obtiene fuentes por cliente
     * 
     * @param string|int $clientId
     * @param bool $activeOnly
     * @return Collection
     */
    public function getByClient(string|int $clientId, bool $activeOnly = false): Collection
    {
        return $activeOnly
            ? $this->sourceRepository->findActiveByClient($clientId)
            : $this->sourceRepository->findByClient($clientId);
    }

    /**
     * Obtiene estadísticas de fuentes
     * 
     * @return array
     */
    public function getStats(): array
    {
        return [
            'total' => $this->sourceRepository->getAll()->count(),
            'active' => $this->sourceRepository->countActive(),
            'by_type' => [
                'whatsapp' => $this->sourceRepository->countByType(SourceType::WHATSAPP),
                'webhook' => $this->sourceRepository->countByType(SourceType::WEBHOOK),
                'meta_whatsapp' => $this->sourceRepository->countByType(SourceType::META_WHATSAPP),
                'facebook_lead_ads' => $this->sourceRepository->countByType(SourceType::FACEBOOK_LEAD_ADS),
                'google_ads' => $this->sourceRepository->countByType(SourceType::GOOGLE_ADS),
            ],
        ];
    }

    /**
     * Valida y obtiene el tipo de fuente
     * 
     * @param string $typeValue
     * @return SourceType
     * @throws ValidationException
     */
    protected function validateAndGetType(string $typeValue): SourceType
    {
        try {
            return SourceType::from($typeValue);
        } catch (\ValueError $e) {
            throw new ValidationException("Tipo de fuente inválido: {$typeValue}");
        }
    }

    /**
     * Valida que el nombre sea único para el cliente
     * 
     * @param string $name
     * @param string|int|null $clientId
     * @param int|null $excludeId
     * @throws ValidationException
     */
    protected function validateUniqueName(string $name, string|int|null $clientId = null, ?int $excludeId = null): void
    {
        if ($this->sourceRepository->existsByName($name, $clientId, $excludeId)) {
            throw new ValidationException(
                'Ya existe una fuente con ese nombre para este cliente'
            );
        }
    }

    /**
     * Valida la configuración según el tipo de fuente
     * 
     * @param SourceType $type
     * @param array $config
     * @throws ValidationException
     */
    protected function validateConfig(SourceType $type, array $config): void
    {
        $required = $type->requiredConfigFields();
        $missing = [];

        foreach ($required as $field) {
            if (empty($config[$field] ?? null)) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new ValidationException(
                'Configuración incompleta. Campos requeridos faltantes: ' . implode(', ', $missing)
            );
        }

        // Validaciones específicas por tipo
        match ($type) {
            SourceType::WEBHOOK => $this->validateWebhookConfig($config),
            SourceType::WHATSAPP => $this->validateWhatsappConfig($config),
            SourceType::META_WHATSAPP => $this->validateMetaWhatsappConfig($config),
            default => null,
        };
    }

    /**
     * Valida configuración de webhook
     */
    protected function validateWebhookConfig(array $config): void
    {
        if (!filter_var($config['url'], FILTER_VALIDATE_URL)) {
            throw new ValidationException('URL de webhook inválida');
        }

        $validMethods = ['GET', 'POST', 'PUT', 'PATCH'];
        if (!in_array(strtoupper($config['method']), $validMethods)) {
            throw new ValidationException('Método HTTP inválido');
        }
    }

    /**
     * Valida configuración de WhatsApp (Evolution API)
     */
    protected function validateWhatsappConfig(array $config): void
    {
        if (!filter_var($config['api_url'], FILTER_VALIDATE_URL)) {
            throw new ValidationException('URL de Evolution API inválida');
        }
    }

    /**
     * Valida configuración de Meta WhatsApp
     */
    protected function validateMetaWhatsappConfig(array $config): void
    {
        if (!is_numeric($config['phone_number_id'])) {
            throw new ValidationException('Phone Number ID inválido');
        }
    }

    /**
     * Actualiza el estado de una fuente
     * 
     * @param int $id
     * @param SourceStatus $status
     * @return Source
     */
    protected function updateStatus(int $id, SourceStatus $status): Source
    {
        return $this->sourceRepository->update($id, [
            'status' => $status->value,
        ]);
    }
}

