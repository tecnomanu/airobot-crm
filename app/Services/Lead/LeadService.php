<?php

namespace App\Services\Lead;

use App\Enums\CampaignActionType;
use App\Enums\LeadAutomationStatus;
use App\Enums\LeadIntention;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Events\LeadUpdated;
use App\Exceptions\Business\ConfigurationException;
use App\Helpers\PhoneHelper;
use App\Models\Lead;
use App\Repositories\Interfaces\CampaignRepositoryInterface;
use App\Repositories\Interfaces\LeadRepositoryInterface;
use App\Services\Webhook\WebhookDispatcherService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeadService
{
    public function __construct(
        private LeadRepositoryInterface $leadRepository,
        private CampaignRepositoryInterface $campaignRepository,
        private WebhookDispatcherService $webhookDispatcher,
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
     * Procesa automáticamente las opciones si están configuradas
     */
    public function createLead(array $data): Lead
    {
        return DB::transaction(function () use ($data) {
            // Validar que la campaña exista
            $campaign = $this->campaignRepository->findById($data['campaign_id']);

            if (! $campaign) {
                throw new \InvalidArgumentException('Campaña no encontrada');
            }

            // Buscar si ya existe un lead con el mismo teléfono y campaña
            $existingLead = $this->leadRepository->findByPhoneAndCampaign(
                $data['phone'],
                $data['campaign_id']
            );

            if ($existingLead) {
                Log::info('Lead existente encontrado, actualizando en lugar de crear duplicado', [
                    'lead_id' => $existingLead->id,
                    'phone' => $data['phone'],
                    'campaign_id' => $data['campaign_id'],
                ]);

                // Actualizar el lead existente con los nuevos datos
                $lead = $this->leadRepository->update($existingLead, array_merge(
                    $data,
                    ['status' => $data['status'] ?? $existingLead->status]
                ));

                // Emitir evento de broadcasting para frontend
                broadcast(new LeadUpdated($lead->load('campaign'), 'updated'))->toOthers();

                return $lead;
            }

            // Setear valores por defecto
            $data['status'] = $data['status'] ?? LeadStatus::PENDING;
            $data['webhook_sent'] = $data['webhook_sent'] ?? false;

            $lead = $this->leadRepository->create($data);

            // Procesar automáticamente si la campaña lo permite
            $this->autoProcessLeadIfEnabled($lead);

            // Emitir evento de broadcasting para frontend
            broadcast(new LeadUpdated($lead->load('campaign'), 'created'))->toOthers();

            return $lead;
        });
    }

    /**
     * Actualizar un lead existente
     */
    public function updateLead(string $id, array $data): Lead
    {
        $lead = $this->leadRepository->findById($id);

        if (! $lead) {
            throw new \InvalidArgumentException('Lead no encontrado');
        }

        $lead = $this->leadRepository->update($lead, $data);

        // Emitir evento de broadcasting para frontend
        broadcast(new LeadUpdated($lead->load('campaign'), 'updated'))->toOthers();

        return $lead;
    }

    /**
     * Eliminar un lead
     */
    public function deleteLead(string $id): bool
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
     * 
     * @deprecated Este método no se usa actualmente. Usar processIncomingWebhookLead() en su lugar.
     */
    public function assignToCampaignByPattern(string $pattern, array $leadData): ?Lead
    {
        // Usar findBySlug en lugar de findByMatchPattern
        $campaign = $this->campaignRepository->findBySlug($pattern);

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
            // Buscar campaña primero para usar su país en la normalización
            $campaign = $this->findCampaignForLead($leadData);

            // Normalizar teléfono con contexto de campaña
            $phone = PhoneHelper::normalizeForLead($leadData['phone'], $campaign);

            if (! PhoneHelper::isValid($phone)) {
                throw new \InvalidArgumentException('Número de teléfono inválido: ' . $leadData['phone']);
            }

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

            $isNewLead = ! $existingLead;

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

            // Emitir evento de broadcasting para frontend
            $action = $isNewLead ? 'created' : 'updated';
            broadcast(new LeadUpdated($lead->load('campaign'), $action))->toOthers();

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

            // Actualizar estado a processing
            $this->leadRepository->update($lead, [
                'automation_status' => LeadAutomationStatus::PROCESSING,
                'last_automation_run_at' => now(),
                'automation_attempts' => $lead->automation_attempts + 1,
            ]);

            $this->optionProcessor->processLeadOption($lead, $option);

            // Determinar el estado final según el tipo de acción
            $finalStatus = match ($option->action) {
                CampaignActionType::MANUAL_REVIEW => LeadAutomationStatus::SKIPPED,
                CampaignActionType::SKIP => LeadAutomationStatus::SKIPPED,
                default => LeadAutomationStatus::COMPLETED,
            };

            // Actualizar estado según el tipo de acción
            $this->leadRepository->update($lead, [
                'automation_status' => $finalStatus,
                'automation_error' => null,
            ]);

            Log::info('Auto-procesamiento completado exitosamente', [
                'lead_id' => $lead->id,
                'automation_status' => $finalStatus->value,
            ]);
        } catch (ConfigurationException $e) {
            // Errores de configuración se marcan como SKIPPED (no es un error técnico)
            $this->leadRepository->update($lead, [
                'automation_status' => LeadAutomationStatus::SKIPPED,
                'automation_error' => $e->getMessage(),
            ]);

            Log::warning('Auto-procesamiento omitido por error de configuración', [
                'lead_id' => $lead->id,
                'option_key' => $option->option_key,
                'error' => $e->getMessage(),
            ]);
            // No lanzar excepción para no bloquear la creación del lead
        } catch (\Exception $e) {
            // Errores técnicos se marcan como FAILED
            $this->leadRepository->update($lead, [
                'automation_status' => LeadAutomationStatus::FAILED,
                'automation_error' => $e->getMessage(),
            ]);

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
            $campaign = $this->campaignRepository->findById($leadData['campaign_id']);
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

    /**
     * Buscar o crear lead desde mensaje de WhatsApp
     * Si no existe el lead, lo crea con los datos disponibles
     */
    public function findOrCreateFromWhatsApp(
        string $phone,
        ?array $whatsappData = null,
        ?int $defaultCampaignId = null
    ): Lead {
        // Normalizar teléfono
        $normalizedPhone = PhoneHelper::normalizeWithCountry($phone, 'AR');

        // Determinar campaña primero
        $campaignId = $defaultCampaignId;
        if (! $campaignId) {
            // Buscar primera campaña activa como fallback
            $activeCampaigns = $this->campaignRepository->getActive();
            if ($activeCampaigns->isNotEmpty()) {
                $campaignId = $activeCampaigns->first()->id;
            } else {
                throw new \Exception('No hay campañas activas disponibles para asociar el lead');
            }
        }

        // Buscar lead con el mismo teléfono Y campaña (evita duplicados)
        $lead = $this->leadRepository->findByPhoneAndCampaign($normalizedPhone, $campaignId);

        if ($lead) {
            Log::info('Lead encontrado por teléfono y campaña', [
                'lead_id' => $lead->id,
                'phone' => $normalizedPhone,
                'campaign_id' => $campaignId,
            ]);

            // Actualizar nombre si viene de WhatsApp y es mejor que el actual
            if ($whatsappData && isset($whatsappData['pushName'])) {
                $this->updateContactInfoFromWhatsApp($lead, $whatsappData);
            }

            return $lead;
        }

        // Si no existe, crear un nuevo lead
        Log::info('Creando nuevo lead desde WhatsApp', [
            'phone' => $normalizedPhone,
            'campaign_id' => $campaignId,
            'has_whatsapp_data' => (bool) $whatsappData,
        ]);

        // Extraer nombre del pushName de WhatsApp si está disponible
        $name = $whatsappData['pushName'] ?? $whatsappData['name'] ?? 'Lead desde WhatsApp';

        return $this->leadRepository->create([
            'phone' => $normalizedPhone,
            'name' => $name,
            'campaign_id' => $campaignId,
            'status' => LeadStatus::PENDING,
            'source' => LeadSource::WHATSAPP,
            'sent_at' => now(),
        ]);
    }

    /**
     * Actualizar intención del lead basado en mensaje recibido
     *
     * Por defecto usa SOLO IA con delay (evita falsos positivos).
     * Opcionalmente puede usar palabras clave primero si está configurado.
     */
    public function updateIntentionFromMessage(Lead $lead, string $messageContent): void
    {
        // Si ya tiene intención finalizada, no procesar
        if ($lead->intention_status === LeadIntentionStatus::FINALIZED) {
            Log::info('Lead ya tiene intención finalizada, saltando análisis', [
                'lead_id' => $lead->id,
                'intention' => $lead->intention,
            ]);

            return;
        }

        $useKeywordsFirst = config('services.openai.use_keywords_first', false);
        $detectedIntention = null;

        // Solo usar palabras clave si está explícitamente habilitado
        if ($useKeywordsFirst) {
            $detectedIntention = $this->analyzeIntentionWithKeywords($messageContent);
        }

        // Preparar datos de actualización
        $updateData = [];

        // Si detectamos una intención clara con palabras clave
        if ($detectedIntention) {
            $updateData['intention'] = $detectedIntention;
            $updateData['intention_status'] = LeadIntentionStatus::FINALIZED;
            $updateData['intention_decided_at'] = now();
            $updateData['intention_origin'] = LeadIntentionOrigin::WHATSAPP;

            // Auto-cambiar status según intención detectada
            if ($detectedIntention === LeadIntention::INTERESTED->value) {
                if ($lead->status === LeadStatus::PENDING) {
                    $updateData['status'] = LeadStatus::IN_PROGRESS;
                }
            } elseif ($detectedIntention === LeadIntention::NOT_INTERESTED->value) {
                $updateData['status'] = LeadStatus::CLOSED;
            }

            Log::info('Intención detectada con palabras clave', [
                'lead_id' => $lead->id,
                'intention' => $detectedIntention,
                'message' => substr($messageContent, 0, 100),
            ]);
        } else {
            // No se detectó con palabras clave (o están deshabilitadas)
            // Guardar mensaje como intención temporal
            $updateData['intention'] = $messageContent;

            // Mover a IN_PROGRESS si es primera respuesta
            if ($lead->status === LeadStatus::PENDING) {
                $updateData['status'] = LeadStatus::IN_PROGRESS;
            }

            // Programar análisis con IA asíncrono con delay (debouncing)
            $this->scheduleAIAnalysis($lead);

            Log::info($useKeywordsFirst ? 'Intención no detectada con palabras clave, programando análisis IA' : 'Programando análisis IA (palabras clave deshabilitadas)', [
                'lead_id' => $lead->id,
                'message' => substr($messageContent, 0, 100),
                'use_keywords' => $useKeywordsFirst,
            ]);
        }

        // Asegurar que el source sea whatsapp
        if ($lead->source !== LeadSource::WHATSAPP) {
            $updateData['source'] = LeadSource::WHATSAPP;
        }

        if (! empty($updateData)) {
            $lead = $this->leadRepository->update($lead, $updateData);

            // Si se detectó intención clara, enviar webhook
            if ($detectedIntention && in_array($detectedIntention, ['interested', 'not_interested'])) {
                try {
                    $this->webhookDispatcher->dispatchLeadIntentionWebhook($lead);
                } catch (\Exception $e) {
                    Log::error('Error al enviar webhook de intención', [
                        'lead_id' => $lead->id,
                        'intention' => $detectedIntention,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Programar análisis con IA de forma asíncrona con debouncing
     *
     * Si llegan múltiples mensajes, solo se procesará una vez después
     * de que pasen X segundos sin nuevos mensajes.
     */
    protected function scheduleAIAnalysis(Lead $lead): void
    {
        $aiAnalyzer = app(LeadIntentionAnalyzerService::class);

        if (! $aiAnalyzer->isEnabled()) {
            return;
        }

        // Incrementar versión para invalidar jobs anteriores (debouncing)
        $cacheKey = "lead_intention_analysis:{$lead->id}";

        // Obtener versión actual o inicializar en 0
        $currentVersion = Cache::get($cacheKey, 0);
        $newVersion = $currentVersion + 1;

        // Guardar nueva versión
        Cache::put($cacheKey, $newVersion, now()->addMinutes(10));

        // Programar job con delay de 8 segundos
        // Si llega otro mensaje, se incrementará la versión y este job se cancelará
        $delay = now()->addSeconds(config('services.openai.analysis_delay_seconds', 8));

        \App\Jobs\Lead\AnalyzeLeadIntentionJob::dispatch($lead->id, $newVersion)
            ->delay($delay)
            ->onQueue('default');

        Log::info('Job de análisis IA programado', [
            'lead_id' => $lead->id,
            'version' => $newVersion,
            'delay_seconds' => config('services.openai.analysis_delay_seconds', 8),
        ]);
    }

    /**
     * Analizar intención solo con palabras clave (sin IA)
     */
    protected function analyzeIntentionWithKeywords(string $content): ?string
    {
        $contentLower = mb_strtolower($content);

        // Palabras clave para "interested"
        $interestedKeywords = [
            'sí',
            'si',
            'yes',
            'interesado',
            'interesada',
            'quiero',
            'me interesa',
            'info',
            'información',
            'mas info',
            'más info',
            'dame',
            'llamame',
            'llámame',
            'contactame',
            'contáctame',
            'ok',
            'dale',
            'perfecto',
            'claro',
            'bueno',
            'bien',
            'hola',
            'buenos dias',
            'buenas tardes',
            'buenas noches',
            'buen dia',
            'gracias',
            'de acuerdo',
        ];

        foreach ($interestedKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                return LeadIntention::INTERESTED->value;
            }
        }

        // Palabras clave para "not_interested"
        $notInterestedKeywords = [
            'no',
            'nope',
            'no gracias',
            'no me interesa',
            'no quiero',
            'no estoy interesado',
            'no estoy interesada',
            'baja',
            'borrar',
            'eliminar',
            'remover',
            'stop',
            'cancelar',
            'no molesten',
            'dejame en paz',
            'no molestar',
        ];

        foreach ($notInterestedKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                return LeadIntention::NOT_INTERESTED->value;
            }
        }

        return null;
    }

    /**
     * Actualizar información de contacto del lead desde WhatsApp
     */
    public function updateContactInfoFromWhatsApp(Lead $lead, array $whatsappData): void
    {
        // Extraer pushName (nombre del contacto en WhatsApp)
        $pushName = $whatsappData['pushName'] ?? $whatsappData['name'] ?? null;

        if (! $pushName) {
            return;
        }

        // Solo actualizar si el nombre actual es genérico o vacío
        if ($this->shouldUpdateName($lead, $pushName)) {
            $this->leadRepository->update($lead, [
                'name' => $pushName,
            ]);

            Log::info('Nombre del lead actualizado desde WhatsApp', [
                'lead_id' => $lead->id,
                'old_name' => $lead->name,
                'new_name' => $pushName,
            ]);
        }
    }

    /**
     * Determinar si debemos actualizar el nombre del lead
     */
    protected function shouldUpdateName(Lead $lead, string $newName): bool
    {
        // Si no tiene nombre, actualizar
        if (empty($lead->name)) {
            return true;
        }

        // Lista de nombres genéricos/placeholder que pueden sobrescribirse
        $genericNames = [
            'Lead sin nombre',
            'Sin nombre',
            'Unknown',
            'N/A',
            'lead',
            'Lead desde WhatsApp',
        ];

        $currentName = trim(strtolower($lead->name));

        foreach ($genericNames as $generic) {
            if (str_contains($currentName, strtolower($generic))) {
                return true;
            }
        }

        // Si el nombre actual es solo el teléfono, actualizar
        if ($lead->name === $lead->phone) {
            return true;
        }

        // No sobrescribir nombres reales
        return false;
    }

    /**
     * Reintentar auto-procesamiento de un lead que falló
     */
    public function retryAutomation(string $id): Lead
    {
        $lead = $this->leadRepository->findById($id, ['campaign.options']);

        if (! $lead) {
            throw new \InvalidArgumentException('Lead no encontrado');
        }

        // Resetear estado de automation
        $this->leadRepository->update($lead, [
            'automation_status' => LeadAutomationStatus::PENDING,
            'automation_error' => null,
        ]);

        // Recargar el lead con los cambios
        $lead->refresh();

        // Intentar procesar nuevamente
        $this->autoProcessLeadIfEnabled($lead);

        // Verificar el estado final después del procesamiento
        $lead->refresh();

        if ($lead->automation_status === LeadAutomationStatus::FAILED) {
            $error = $lead->automation_error ?? 'Error desconocido en auto-procesamiento';
            Log::error('Reintento de auto-procesamiento falló', [
                'lead_id' => $lead->id,
                'error' => $error,
            ]);
            throw new \Exception('Error al reintentar auto-procesamiento: ' . $error);
        }

        Log::info('Reintento de auto-procesamiento exitoso', [
            'lead_id' => $lead->id,
            'automation_status' => $lead->automation_status->value,
        ]);

        return $lead->fresh();
    }

    /**
     * Reintentar auto-procesamiento para múltiples leads
     */
    public function retryAutomationBatch(array $filters = []): array
    {
        // Buscar leads pendientes o fallidos
        $leads = $this->leadRepository->getFailedAutomation($filters);

        $results = [
            'total' => $leads->count(),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($leads as $lead) {
            try {
                $this->retryAutomation($lead->id);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'lead_id' => $lead->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        Log::info('Reintento masivo de auto-procesamiento', $results);

        return $results;
    }

    /**
     * Obtener leads pendientes de procesamiento o fallidos
     */
    public function getPendingAutomation(array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $this->leadRepository->getPendingAutomation($filters);
    }
}
