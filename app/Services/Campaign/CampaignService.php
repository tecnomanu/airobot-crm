<?php

namespace App\Services\Campaign;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Exceptions\Business\ValidationException;
use App\Models\Campaign\Campaign;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\ClientRepositoryInterface;
use App\Repositories\Interfaces\SourceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(
        private CampaignRepositoryInterface $campaignRepository,
        private ClientRepositoryInterface $clientRepository,
        private SourceRepositoryInterface $sourceRepository
    ) {}

    /**
     * Obtener campañas paginadas con filtros
     */
    public function getCampaigns(array $filters = [], int $perPage = 15)
    {
        return $this->campaignRepository->paginate($filters, $perPage);
    }

    /**
     * Obtener una campaña por ID con todas sus relaciones
     */
    public function getCampaignById(string $id): ?Campaign
    {
        return $this->campaignRepository->findById($id, [
            'client',
            'creator',
            'leads',
            'callAgent',
            'whatsappAgent.source',
            'options.source',
            'options.template',
            'whatsappTemplates',
            'intentionActions.webhook',
            'intentionActions.googleIntegration',
        ]);
    }

    /**
     * Crear una nueva campaña con todas sus relaciones
     */
    public function createCampaign(array $data): Campaign
    {
        // Validar que el cliente exista
        $client = $this->clientRepository->findById($data['client_id']);

        if (! $client) {
            throw new \InvalidArgumentException('Cliente no encontrado');
        }

        return DB::transaction(function () use ($data) {
            // Generar slug único si no se proporciona
            $slug = $data['slug'] ?? $this->generateUniqueSlug($data['name']);

            // Crear campaña base
            $campaign = $this->campaignRepository->create([
                'name' => $data['name'],
                'client_id' => $data['client_id'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'slug' => $slug,
                'strategy_type' => $data['strategy_type'] ?? 'dynamic',
                'auto_process_enabled' => $data['auto_process_enabled'] ?? true,
                'created_by' => $data['created_by'] ?? null,
            ]);

            // Crear agente de llamadas si viene en los datos
            if (isset($data['call_agent'])) {
                $this->createOrUpdateCallAgent($campaign, $data['call_agent']);
            }

            // Crear agente de WhatsApp si viene en los datos
            if (isset($data['whatsapp_agent'])) {
                $this->createOrUpdateWhatsappAgent($campaign, $data['whatsapp_agent']);
            }

            // Crear intention actions
            $this->syncIntentionActions($campaign, $data);

            // Crear opciones
            $strategyType = $data['strategy_type'] ?? 'dynamic';
            if ($strategyType === 'direct') {
                // Para campañas directas, crear una sola opción con key='0'
                if (isset($data['direct_action'])) {
                    $directOption = [
                        'option_key' => '0',
                        'action' => $data['direct_action'],
                        'source_id' => $data['direct_source_id'] ?? null,
                        'template_id' => $data['direct_template_id'] ?? null,
                        'message' => $data['direct_message'] ?? null,
                        'delay' => $data['direct_delay'] ?? 5,
                        'enabled' => true,
                    ];
                    $this->syncOptions($campaign, [$directOption]);
                }
            } else {
                // Para campañas dinámicas (por defecto: 1, 2, i, t)
                $options = $data['options'] ?? $this->getDefaultOptions();
                $this->syncOptions($campaign, $options);
            }

            return $campaign->fresh([
                'callAgent',
                'whatsappAgent.source',
                'options.source',
                'options.template',
                'intentionActions.webhook',
                'intentionActions.googleIntegration',
            ]);
        });
    }

    /**
     * Actualizar una campaña existente
     */
    public function updateCampaign(string $id, array $data): Campaign
    {
        $campaign = $this->campaignRepository->findById($id);

        if (! $campaign) {
            throw new \InvalidArgumentException('Campaña no encontrada');
        }

        return DB::transaction(function () use ($campaign, $data) {
            // Actualizar datos base de la campaña
            $baseData = array_intersect_key($data, array_flip([
                'name',
                'slug',
                'description',
                'status',
                'auto_process_enabled',
                'use_client_call_defaults',
                'use_client_whatsapp_defaults',
                'no_response_action_enabled',
                'no_response_max_attempts',
                'no_response_timeout_hours',
            ]));

            // Si se proporciona slug, normalizarlo
            if (isset($baseData['slug']) && !empty($baseData['slug'])) {
                $baseData['slug'] = strtolower(trim($baseData['slug']));
            }

            // Guardar siempre que haya datos, incluso si son false o null
            if (count($baseData) > 0) {
                $campaign = $this->campaignRepository->update($campaign, $baseData);
            }

            // Actualizar agente de llamadas
            if (isset($data['call_agent'])) {
                $this->createOrUpdateCallAgent($campaign, $data['call_agent']);
            }

            // Actualizar agente de WhatsApp
            if (isset($data['whatsapp_agent'])) {
                $this->createOrUpdateWhatsappAgent($campaign, $data['whatsapp_agent']);
            }

            // Actualizar intention actions
            $this->syncIntentionActions($campaign, $data);

            // Actualizar opciones
            if ($campaign->isDirect()) {
                // Para campañas directas, actualizar opción con key='0'
                if (isset($data['direct_action'])) {
                    $directOption = [
                        'option_key' => '0',
                        'action' => $data['direct_action'],
                        'source_id' => $data['direct_source_id'] ?? null,
                        'template_id' => $data['direct_template_id'] ?? null,
                        'message' => $data['direct_message'] ?? null,
                        'delay' => $data['direct_delay'] ?? 5,
                        'enabled' => true,
                    ];
                    $this->syncOptions($campaign, [$directOption]);
                }
            } else {
                // Para campañas dinámicas
                if (isset($data['options'])) {
                    $this->syncOptions($campaign, $data['options']);
                }
            }

            // Sync assignees (vendedores) if provided
            if (array_key_exists('assignee_user_ids', $data)) {
                $this->syncAssignees($campaign, $data['assignee_user_ids'] ?? []);
            }

            return $campaign->fresh([
                'callAgent',
                'whatsappAgent.source',
                'options.source',
                'options.template',
                'intentionActions.webhook',
                'intentionActions.googleIntegration',
                'assignees.user',
            ]);
        });
    }

    /**
     * Eliminar una campaña
     * CUIDADO: Esto eliminará todos los leads y relaciones asociadas (cascade)
     */
    public function deleteCampaign(string $id): bool
    {
        $campaign = $this->campaignRepository->findById($id);

        if (! $campaign) {
            throw new \InvalidArgumentException('Campaña no encontrada');
        }

        return $this->campaignRepository->delete($campaign);
    }

    /**
     * Obtener campañas activas
     */
    public function getActiveCampaigns()
    {
        return $this->campaignRepository->getActive();
    }

    /**
     * Obtener campañas activas para un cliente específico (tenant isolation)
     */
    public function getActiveCampaignsForClient(string $clientId)
    {
        return Campaign::where('client_id', $clientId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'status']);
    }

    /**
     * Obtener campañas de un cliente
     */
    public function getCampaignsByClient(string $clientId)
    {
        return $this->campaignRepository->getByClient($clientId);
    }

    /**
     * Obtener conteo de leads de una campaña
     */
    public function getLeadsCount(string $campaignId): int
    {
        return $this->campaignRepository->getLeadsCount($campaignId);
    }

    /**
     * Crear o actualizar el agente de llamadas de una campaña
     */
    protected function createOrUpdateCallAgent(Campaign $campaign, array $data): void
    {
        $campaign->callAgent()->updateOrCreate(
            ['campaign_id' => $campaign->id],
            [
                'name' => $data['name'],
                'provider' => $data['provider'],
                'config' => $data['config'] ?? [],
                'enabled' => $data['enabled'] ?? true,
            ]
        );
    }

    /**
     * Crear o actualizar el agente de WhatsApp de una campaña
     */
    protected function createOrUpdateWhatsappAgent(Campaign $campaign, array $data): void
    {
        // Validar fuente de WhatsApp si se proporciona
        if (isset($data['source_id'])) {
            $this->validateWhatsappSource($data['source_id']);
        }

        $campaign->whatsappAgent()->updateOrCreate(
            ['campaign_id' => $campaign->id],
            [
                'name' => $data['name'],
                'source_id' => $data['source_id'] ?? null,
                'config' => $data['config'] ?? [],
                'enabled' => $data['enabled'] ?? true,
            ]
        );
    }

    /**
     * Sincronizar acciones de intención (interested/not_interested)
     */
    protected function syncIntentionActions(Campaign $campaign, array $data): void
    {
        // Acción para interesados
        $interestedData = [
            'intention_type' => 'interested',
            'action_type' => 'none',
            'enabled' => false,
        ];

        if (isset($data['intention_interested_webhook_id']) && !empty($data['intention_interested_webhook_id'])) {
            $interestedData['action_type'] = 'webhook';
            $interestedData['webhook_id'] = $data['intention_interested_webhook_id'];
            $interestedData['enabled'] = $data['send_intention_interested_webhook'] ?? false;
        } elseif (isset($data['google_spreadsheet_id']) && !empty($data['google_spreadsheet_id'])) {
            $interestedData['action_type'] = 'spreadsheet';
            $interestedData['google_integration_id'] = $data['google_integration_id'] ?? null;
            $interestedData['google_spreadsheet_id'] = $data['google_spreadsheet_id'];
            $interestedData['google_sheet_name'] = $data['google_sheet_name'] ?? null;
            $interestedData['enabled'] = true;
        }

        $campaign->intentionActions()->updateOrCreate(
            ['campaign_id' => $campaign->id, 'intention_type' => 'interested'],
            $interestedData
        );

        // Acción para no interesados
        $notInterestedData = [
            'intention_type' => 'not_interested',
            'action_type' => 'none',
            'enabled' => false,
        ];

        if (isset($data['intention_not_interested_webhook_id']) && !empty($data['intention_not_interested_webhook_id'])) {
            $notInterestedData['action_type'] = 'webhook';
            $notInterestedData['webhook_id'] = $data['intention_not_interested_webhook_id'];
            $notInterestedData['enabled'] = $data['send_intention_not_interested_webhook'] ?? false;
        } elseif (isset($data['intention_not_interested_google_spreadsheet_id']) && !empty($data['intention_not_interested_google_spreadsheet_id'])) {
            $notInterestedData['action_type'] = 'spreadsheet';
            $notInterestedData['google_spreadsheet_id'] = $data['intention_not_interested_google_spreadsheet_id'];
            $notInterestedData['google_sheet_name'] = $data['intention_not_interested_google_sheet_name'] ?? null;
            $notInterestedData['enabled'] = true;
        }

        $campaign->intentionActions()->updateOrCreate(
            ['campaign_id' => $campaign->id, 'intention_type' => 'not_interested'],
            $notInterestedData
        );
    }

    /**
     * Sincronizar opciones de la campaña
     * Las opciones por defecto son: 1, 2, i, t
     */
    protected function syncOptions(Campaign $campaign, array $options): void
    {
        foreach ($options as $optionData) {
            // Validar fuente si se proporciona
            if (isset($optionData['source_id']) && ! empty($optionData['source_id'])) {
                $source = $this->sourceRepository->findById($optionData['source_id']);
                if (! $source) {
                    throw new ValidationException('Una de las fuentes seleccionadas no existe');
                }

                // Validate status
                if ($source->status !== SourceStatus::ACTIVE) {
                    throw new ValidationException("La fuente '{$source->name}' no está activa");
                }

                // Validate type based on action if possible
                if (isset($optionData['action'])) {
                    $action = $optionData['action'];
                    // Handle enum
                    if ($action instanceof \BackedEnum) {
                        $action = $action->value;
                    }

                    if ($action === 'whatsapp' && !$source->type->isMessaging()) {
                        throw new ValidationException("La fuente debe ser de tipo WhatsApp para la acción WhatsApp");
                    }
                    if ($action === 'webhook_crm' && $source->type !== SourceType::WEBHOOK) {
                        throw new ValidationException("La fuente debe ser de tipo Webhook para la acción Webhook CRM");
                    }
                }
            }

            $campaign->options()->updateOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'option_key' => $optionData['option_key'],
                ],
                [
                    'action' => $optionData['action'] ?? 'none',
                    'source_id' => $optionData['source_id'] ?? null,
                    'template_id' => $optionData['template_id'] ?? null,
                    'message' => $optionData['message'] ?? null,
                    'delay' => $optionData['delay'] ?? 5,
                    'enabled' => $optionData['enabled'] ?? true,
                ]
            );
        }
    }

    /**
     * Obtener estructura de opciones por defecto
     */
    protected function getDefaultOptions(): array
    {
        return [
            ['option_key' => '1', 'action' => 'skip', 'enabled' => true, 'delay' => 5],
            ['option_key' => '2', 'action' => 'skip', 'enabled' => true, 'delay' => 5],
            ['option_key' => 'i', 'action' => 'skip', 'enabled' => true, 'delay' => 5],
            ['option_key' => 't', 'action' => 'skip', 'enabled' => true, 'delay' => 5],
        ];
    }

    /**
     * Valida que una fuente de WhatsApp exista, esté activa y sea del tipo correcto
     */
    protected function validateWhatsappSource(string $sourceId): void
    {
        if (empty($sourceId)) {
            return;
        }

        $source = $this->sourceRepository->findById($sourceId);

        if (! $source) {
            throw new ValidationException('La fuente de WhatsApp seleccionada no existe');
        }

        if ($source->status !== SourceStatus::ACTIVE) {
            throw new ValidationException(
                'La fuente de WhatsApp debe estar activa. Estado actual: ' . $source->status->label()
            );
        }

        if (! $source->type->isMessaging()) {
            throw new ValidationException(
                'La fuente seleccionada no es de tipo WhatsApp. Tipo actual: ' . $source->type->label()
            );
        }
    }

    /**
     * Valida que una fuente de Webhook exista, esté activa y sea del tipo correcto
     */
    protected function validateWebhookSource(string $sourceId): void
    {
        if (empty($sourceId)) {
            return;
        }

        $source = $this->sourceRepository->findById($sourceId);

        if (! $source) {
            throw new ValidationException('La fuente de Webhook seleccionada no existe');
        }

        if ($source->status !== SourceStatus::ACTIVE) {
            throw new ValidationException(
                'La fuente de Webhook debe estar activa. Estado actual: ' . $source->status->label()
            );
        }

        if ($source->type !== SourceType::WEBHOOK) {
            throw new ValidationException(
                'La fuente seleccionada no es de tipo Webhook. Tipo actual: ' . $source->type->label()
            );
        }
    }

    /**
     * Sync campaign assignees (vendedores).
     */
    protected function syncAssignees(Campaign $campaign, array $userIds): void
    {
        // Delete existing assignees
        $campaign->assignees()->delete();

        // Create new assignees in order
        foreach ($userIds as $index => $userId) {
            $campaign->assignees()->create([
                'user_id' => $userId,
                'sort_order' => $index,
                'is_active' => true,
            ]);
        }

        // Reset assignment cursor if it exists
        if ($campaign->assignmentCursor) {
            $campaign->assignmentCursor()->update([
                'current_index' => 0,
            ]);
        }
    }

    /**
     * Generar slug único para una campaña
     */
    protected function generateUniqueSlug(string $name): string
    {
        $baseSlug = \Illuminate\Support\Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        // Asegurar que el slug sea único
        while (Campaign::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Cambiar el estado de una campaña (active <-> paused)
     */
    public function toggleCampaignStatus(string $id): Campaign
    {
        $campaign = $this->campaignRepository->findById($id);

        if (! $campaign) {
            throw new \InvalidArgumentException('Campaña no encontrada');
        }

        $newStatus = $campaign->status->value === 'active' ? 'paused' : 'active';

        return $this->campaignRepository->update($campaign, [
            'status' => $newStatus,
        ]);
    }
}
