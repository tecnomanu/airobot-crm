<?php

declare(strict_types=1);

namespace App\Http\Resources\Source;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'type_base' => $this->type->typeLabel(),
            'provider' => $this->type->provider(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'config' => $this->config,
            'client_id' => $this->client_id,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Resumen de config para tabla
            'config_summary' => $this->getConfigSummary(),

            // Campos computados
            'is_active' => $this->isActive(),
            'is_messaging' => $this->isMessaging(),
            'is_whatsapp' => $this->type->isWhatsApp(),
            'is_webhook' => $this->type->isWebhook(),
            'campaigns_count' => $this->when(
                isset($this->campaigns_count),
                $this->campaigns_count
            ),

            // Relaciones opcionales
            'client' => $this->whenLoaded('client', function () {
                return [
                    'id' => $this->client->id,
                    'name' => $this->client->name,
                    'company' => $this->client->company,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
        ];
    }

    /**
     * Obtiene resumen de la configuración para mostrar en tabla
     */
    protected function getConfigSummary(): string
    {
        return match ($this->type->value) {
            'whatsapp', 'meta_whatsapp' => $this->getConfigValue('instance_name', 'N/A'),
            'webhook' => $this->getConfigValue('url', 'N/A'),
            default => 'Configurado',
        };
    }
}
