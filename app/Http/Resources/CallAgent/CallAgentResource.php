<?php

namespace App\Http\Resources\CallAgent;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CallAgentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Si es un array simple (respuesta directa de Retell API)
        if (is_array($this->resource) && ! isset($this->resource['id'])) {
            return $this->resource;
        }

        // Si es un array con estructura de Retell
        if (is_array($this->resource)) {
            return [
                'id' => $this->resource['agent_id'] ?? $this->resource['id'] ?? null,
                'agent_id' => $this->resource['agent_id'] ?? null,
                'agent_name' => $this->resource['agent_name'] ?? null,
                'version' => $this->resource['version'] ?? 0,
                'is_published' => $this->resource['is_published'] ?? false,
                'voice_id' => $this->resource['voice_id'] ?? null,
                'language' => $this->resource['language'] ?? null,
                'webhook_url' => $this->resource['webhook_url'] ?? null,
                'created_at' => $this->resource['created_at'] ?? null,
                'updated_at' => $this->resource['updated_at'] ?? null,
                'last_modification_timestamp' => $this->resource['last_modification_timestamp'] ?? null,
                'response_engine' => $this->resource['response_engine'] ?? null,
                'retell_llm' => $this->resource['retell_llm'] ?? null,
            ];
        }

        // Si es un modelo Eloquent (por si acaso)
        return [
            'id' => $this->id,
            'agent_id' => $this->agent_id,
            'agent_name' => $this->agent_name,
            'voice_id' => $this->voice_id,
            'language' => $this->language,
            'webhook_url' => $this->webhook_url,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

