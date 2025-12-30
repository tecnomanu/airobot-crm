<?php

namespace App\Services\AI;

use App\Models\AI\AgentTemplate;
use App\Models\AI\CampaignAgent;
use Illuminate\Support\Facades\Log;
use OpenAI;

/**
 * Service for composing agent prompts using LLM
 *
 * Takes an agent template and intention prompt, generates the flow section using GPT,
 * and composes the final prompt by combining all sections.
 */
class PromptComposerService
{
    protected $client;
    protected bool $enabled;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key');
        $baseUrl = config('services.openai.base_url');
        $this->enabled = !empty($apiKey);

        if ($this->enabled && $apiKey) {
            if ($baseUrl) {
                $this->client = OpenAI::factory()
                    ->withApiKey($apiKey)
                    ->withBaseUri($baseUrl)
                    ->make();
            } else {
                $this->client = OpenAI::client($apiKey);
            }
        }
    }

    /**
     * Check if the service is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Generate the flow section using LLM based on intention prompt
     *
     * @param AgentTemplate $template The agent template
     * @param string $intentionPrompt User's intention description
     * @param array $variables Campaign-specific variables
     * @return string Generated flow section
     *
     * @throws \Exception If generation fails
     */
    public function generateFlowSection(
        AgentTemplate $template,
        string $intentionPrompt,
        array $variables = []
    ): string {
        if (!$this->enabled) {
            throw new \Exception('OpenAI is not configured. Please set OPENAI_API_KEY in your .env file');
        }

        try {
            $systemPrompt = $this->buildSystemPrompt($template);
            $userPrompt = $this->buildUserPrompt($intentionPrompt, $variables);

            Log::info('Generating flow section with LLM', [
                'template_type' => $template->type->value,
                'intention_preview' => substr($intentionPrompt, 0, 100),
            ]);

            $response = $this->client->chat()->create([
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $systemPrompt,
                    ],
                    [
                        'role' => 'user',
                        'content' => $userPrompt,
                    ],
                ],
                'max_tokens' => config('services.openai.max_tokens_prompt', 2000),
                'temperature' => 0.7, // Balance between creativity and consistency
            ]);

            $flowSection = trim($response->choices[0]->message->content ?? '');

            if (empty($flowSection)) {
                throw new \Exception('LLM returned empty flow section');
            }

            Log::info('Flow section generated successfully', [
                'tokens_used' => $response->usage->totalTokens ?? 0,
                'flow_length' => strlen($flowSection),
            ]);

            return $flowSection;
        } catch (\Exception $e) {
            Log::error('Error generating flow section with LLM', [
                'error' => $e->getMessage(),
                'template_id' => $template->id,
            ]);

            throw new \Exception('Error al generar el flujo conversacional: ' . $e->getMessage());
        }
    }

    /**
     * Compose the final prompt by combining all sections
     *
     * @param CampaignAgent $agent The campaign agent
     * @return string Final composed prompt
     */
    public function composeFinalPrompt(CampaignAgent $agent): string
    {
        $template = $agent->template;

        if (!$template) {
            throw new \Exception('Agent template not found');
        }

        $sections = [];

        // 1. ROL E INTENCIÓN - expandido con variables
        $sections[] = $this->buildRoleSection($agent, $template);

        // 2. FLUJO - Generado por LLM o existente
        if (!empty($agent->flow_section)) {
            $sections[] = $agent->flow_section;
        }

        // 3. DATOS - Template con variables reemplazadas
        if (!empty($template->data_section_template)) {
            $sections[] = $agent->replaceVariables($template->data_section_template);
        }

        // 4. ESTILO - Fijo del template
        if (!empty($template->style_section)) {
            $sections[] = $template->style_section;
        }

        // 5. COMPORTAMIENTO - Fijo del template
        if (!empty($template->behavior_section)) {
            $sections[] = $template->behavior_section;
        }

        return implode("\n\n---\n\n", array_filter($sections));
    }

    /**
     * Validate a generated prompt
     *
     * @param string $prompt The prompt to validate
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validatePrompt(string $prompt): array
    {
        $errors = [];

        // Check minimum length
        if (strlen($prompt) < 100) {
            $errors[] = 'El prompt es demasiado corto (mínimo 100 caracteres)';
        }

        // Check maximum length (Retell tiene límites)
        if (strlen($prompt) > 50000) {
            $errors[] = 'El prompt es demasiado largo (máximo 50,000 caracteres)';
        }

        // Check for unclosed variables
        if (preg_match('/\{\{[^}]*$/', $prompt)) {
            $errors[] = 'El prompt contiene variables sin cerrar correctamente';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    // =========================================================================
    // PRIVATE HELPERS
    // =========================================================================

    /**
     * Build system prompt for LLM
     */
    private function buildSystemPrompt(AgentTemplate $template): string
    {
        return <<<PROMPT
Eres un experto en diseño de prompts para agentes conversacionales de IA especializados en llamadas telefónicas.

Tu tarea es generar ÚNICAMENTE la **sección de FLUJO CONVERSACIONAL** para un agente de tipo "{$template->type->label()}".

**Contexto del template:**
- **Tipo de agente**: {$template->type->label()}
- **Descripción**: {$template->description}

**Variables disponibles**: {$this->formatVariables($template->getAvailableVariables())}

**Instrucciones:**
1. Genera un flujo conversacional detallado y natural en español
2. El flujo debe tener estructura clara con:
   - Apertura/Saludo
   - Propuesta/Oferta principal
   - Manejo de respuestas (acepta, rechaza, necesita más info)
   - Cierre apropiado según cada caso
3. Usa los placeholders de variables como {{variable}} cuando sea necesario
4. Sé específico en las etapas del flujo pero mantén flexibilidad para conversaciones naturales
5. Incluye manejo de objeciones o preguntas comunes
6. NO incluyas las secciones de ESTILO, COMPORTAMIENTO o DATOS - solo el FLUJO
7. Usa formato markdown con headers (##) para organizar las secciones del flujo

**Ejemplo de estructura esperada:**
```
## Apertura
[Descripción del saludo inicial]

## Propuesta
[Presentación de la oferta principal]

## Manejo de Respuestas

### Opción 1: Acepta
[Qué hacer si acepta]

### Opción 2: Rechaza
[Qué hacer si rechaza]

## Cierre
[Cómo finalizar la conversación]
```

Genera SOLO el contenido del flujo, sin explicaciones adicionales.
PROMPT;
    }

    /**
     * Build user prompt with intention and variables
     */
    private function buildUserPrompt(string $intentionPrompt, array $variables): string
    {
        $prompt = "**Intención del agente:**\n{$intentionPrompt}\n\n";

        if (!empty($variables)) {
            $prompt .= "**Variables específicas:**\n";
            foreach ($variables as $key => $value) {
                $prompt .= "- {$key}: {$value}\n";
            }
            $prompt .= "\n";
        }

        $prompt .= "Genera el flujo conversacional completo para este agente.";

        return $prompt;
    }

    /**
     * Build role and intention section
     */
    private function buildRoleSection(CampaignAgent $agent, AgentTemplate $template): string
    {
        $intention = $agent->replaceVariables($agent->intention_prompt);

        return <<<SECTION
# ROL E INTENCIÓN

{$intention}

**Tipo de agente**: {$template->type->label()}
SECTION;
    }

    /**
     * Format variables list for display
     */
    private function formatVariables(array $variables): string
    {
        if (empty($variables)) {
            return 'ninguna';
        }

        return implode(', ', array_map(fn($v) => "{{$v}}", $variables));
    }
}
