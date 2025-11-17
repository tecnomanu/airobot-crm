<?php

namespace App\Services\Lead;

use App\Enums\LeadIntention;
use App\Enums\LeadIntentionOrigin;
use App\Enums\LeadIntentionStatus;
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
            // Normalizar teléfono
            $phone = PhoneHelper::normalize($leadData['phone']);

            if (! PhoneHelper::isValid($phone)) {
                throw new \InvalidArgumentException('Número de teléfono inválido: ' . $leadData['phone']);
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

        // Buscar lead con variantes de teléfono
        $lead = $this->leadRepository->findByPhoneWithVariants($normalizedPhone);

        if ($lead) {
            Log::info('Lead encontrado por teléfono', [
                'lead_id' => $lead->id,
                'phone' => $normalizedPhone,
            ]);

            return $lead;
        }

        // Si no existe, crear un nuevo lead
        Log::info('Creando nuevo lead desde WhatsApp', [
            'phone' => $normalizedPhone,
            'has_whatsapp_data' => (bool) $whatsappData,
        ]);

        // Extraer nombre del pushName de WhatsApp si está disponible
        $name = $whatsappData['pushName'] ?? $whatsappData['name'] ?? 'Lead desde WhatsApp';

        // Determinar campaña
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
     */
    public function updateIntentionFromMessage(Lead $lead, string $messageContent): void
    {
        // Analizar palabras clave para determinar intención
        $detectedIntention = $this->analyzeIntention($messageContent);

        // Preparar datos de actualización
        $updateData = [];

        // Si detectamos una intención clara, guardarla como string simple
        if ($detectedIntention) {
            $updateData['intention'] = $detectedIntention;
            $updateData['intention_status'] = LeadIntentionStatus::FINALIZED;
            $updateData['intention_decided_at'] = now();

            // Si no tenía origin, asignar WhatsApp
            if (! $lead->intention_origin) {
                $updateData['intention_origin'] = LeadIntentionOrigin::WHATSAPP;
            }

            // Auto-cambiar status según intención detectada
            if ($detectedIntention === LeadIntention::INTERESTED->value) {
                // Si está interesado, pasar a IN_PROGRESS para que un agente lo atienda
                if ($lead->status === LeadStatus::PENDING) {
                    $updateData['status'] = LeadStatus::IN_PROGRESS;
                }
            } elseif ($detectedIntention === LeadIntention::NOT_INTERESTED->value) {
                // Si no está interesado, cerrar automáticamente
                $updateData['status'] = LeadStatus::CLOSED;
            }
        } else {
            // Si no hay intención clara, solo actualizar si no tiene una ya finalizada
            if (! $lead->intention_status || $lead->intention_status !== LeadIntentionStatus::FINALIZED) {
                // Guardar el último mensaje como intención temporal
                $updateData['intention'] = $messageContent;
            }

            // Si el lead responde por primera vez, moverlo a IN_PROGRESS
            if ($lead->status === LeadStatus::PENDING) {
                $updateData['status'] = LeadStatus::IN_PROGRESS;
            }
        }

        // Asegurar que el source sea whatsapp si recibió mensaje por WhatsApp
        if ($lead->source !== LeadSource::WHATSAPP) {
            $updateData['source'] = LeadSource::WHATSAPP;
        }

        if (! empty($updateData)) {
            $this->leadRepository->update($lead, $updateData);
        }

        Log::info('Intención del lead actualizada', [
            'lead_id' => $lead->id,
            'detected_intention' => $detectedIntention,
            'new_status' => $updateData['status'] ?? 'unchanged',
            'content_length' => strlen($messageContent),
            'finalized' => (bool) $detectedIntention,
        ]);
    }

    /**
     * Analizar contenido del mensaje para detectar intención
     */
    protected function analyzeIntention(string $content): ?string
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
        ];

        foreach ($notInterestedKeywords as $keyword) {
            if (str_contains($contentLower, $keyword)) {
                return LeadIntention::NOT_INTERESTED->value;
            }
        }

        // Si no detectamos intención clara, retornar null
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
}
