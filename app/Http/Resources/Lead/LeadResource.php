<?php

namespace App\Http\Resources\Lead;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'name' => $this->name,
            'city' => $this->city,
            'option_selected' => $this->option_selected,
            'option_selected_label' => $this->getOptionLabel(),
            'campaign_id' => $this->campaign_id,
            'campaign' => [
                'id' => $this->campaign?->id,
                'name' => $this->campaign?->name,
            ],
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'status_color' => $this->status?->color(),
            'source' => $this->source,
            'source_label' => $this->getSourceLabel(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'intention' => $this->intention,
            'notes' => $this->notes,
            'tags' => $this->tags,
            'webhook_sent' => $this->webhook_sent,
            'webhook_result' => $this->webhook_result,
            'automation_status' => $this->automation_status,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Obtiene el label descriptivo para option_selected
     */
    private function getOptionLabel(): ?string
    {
        if (! $this->option_selected) {
            return null;
        }

        return match ($this->option_selected) {
            '1' => 'Opción 1',
            '2' => 'Opción 2',
            'i' => 'Interesado',
            't' => 'Transferir',
            default => 'Opción '.$this->option_selected,
        };
    }

    /**
     * Obtiene el label descriptivo para source (mapeo de valores comunes)
     */
    private function getSourceLabel(): string
    {
        if (! $this->source) {
            return 'Desconocido';
        }

        // Mapeo de fuentes comunes a labels amigables
        $sourceMap = [
            'webhook_inicial' => 'Webhook Inicial',
            'webhook_event' => 'Webhook por Evento',
            'whatsapp' => 'WhatsApp',
            'agente_ia' => 'Agente IA',
            'manual' => 'Manual',
            'landing_page' => 'Landing Page',
            'formulario' => 'Formulario Web',
            'facebook' => 'Facebook',
            'google_ads' => 'Google Ads',
            'chat_web' => 'Chat Web',
        ];

        // Si existe en el mapeo, usar el label; sino, formatear el valor
        return $sourceMap[$this->source] ?? $this->formatSourceLabel($this->source);
    }

    /**
     * Formatea el source para que sea más legible cuando no está en el mapeo
     */
    private function formatSourceLabel(string $source): string
    {
        // Reemplazar guiones bajos y guiones por espacios
        $formatted = str_replace(['_', '-'], ' ', $source);

        // Capitalizar cada palabra
        return ucwords($formatted);
    }
}
