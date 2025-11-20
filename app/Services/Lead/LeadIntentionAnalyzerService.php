<?php

namespace App\Services\Lead;

use App\Enums\LeadIntention;
use Illuminate\Support\Facades\Log;
use OpenAI;

/**
 * Servicio para analizar intenciones de leads usando IA
 *
 * Analiza mensajes de texto para determinar si el lead está interesado,
 * no interesado, o si necesita más información.
 */
class LeadIntentionAnalyzerService
{
    protected $client;

    protected bool $enabled;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key');
        $baseUrl = config('services.openai.base_url');
        $this->enabled = config('services.openai.analyze_intentions', false) && ! empty($apiKey);

        if ($this->enabled && $apiKey) {
            // Si hay una URL base personalizada (ej: OpenRouter), usarla
            if ($baseUrl) {
                $this->client = OpenAI::factory()
                    ->withApiKey($apiKey)
                    ->withBaseUri($baseUrl)
                    ->make();
            } else {
                // URL por defecto de OpenAI
                $this->client = OpenAI::client($apiKey);
            }
        }
    }

    /**
     * Verificar si el análisis con IA está habilitado
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Analizar múltiples mensajes con contexto conversacional
     *
     * @param  array  $messages  Array de mensajes del lead (orden cronológico)
     * @param  string|null  $campaignContext  Contexto opcional de la campaña
     * @return string|null Retorna 'interested', 'not_interested' o null si no puede determinarse
     */
    public function analyzeIntentionWithContext(array $messages, ?string $campaignContext = null): ?string
    {
        if (! $this->enabled) {
            return null;
        }

        if (empty($messages)) {
            return null;
        }

        try {
            $prompt = $this->buildPromptWithContext($messages, $campaignContext);

            $response = $this->client->chat()->create([
                'model' => config('services.openai.model', 'gpt-4o-mini'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->getSystemPrompt(),
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => config('services.openai.max_tokens', 150),
                'temperature' => config('services.openai.temperature', 0.3),
            ]);

            $result = trim(strtolower($response->choices[0]->message->content ?? ''));

            // Parsear respuesta
            $intention = $this->parseIntentionFromResponse($result);

            Log::info('Análisis de intención con IA completado', [
                'messages_count' => count($messages),
                'first_message' => substr($messages[0] ?? '', 0, 50),
                'result' => $result,
                'intention' => $intention,
                'tokens_used' => $response->usage->totalTokens ?? 0,
            ]);

            return $intention;
        } catch (\Exception $e) {
            Log::error('Error al analizar intención con IA', [
                'messages_count' => count($messages),
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Construir prompt para el análisis (mensaje único)
     */
    protected function buildPrompt(string $message, ?string $campaignContext): string
    {
        return $this->buildPromptWithContext([$message], $campaignContext);
    }

    /**
     * Construir prompt con múltiples mensajes (contexto conversacional)
     */
    protected function buildPromptWithContext(array $messages, ?string $campaignContext): string
    {
        $prompt = "Analiza la siguiente conversación de un lead";

        if ($campaignContext) {
            $prompt .= " en el contexto de la campaña: {$campaignContext}";
        }

        $prompt .= "\n\n";

        if (count($messages) === 1) {
            $prompt .= "Respuesta del lead: \"{$messages[0]}\"";
        } else {
            $prompt .= "Conversación del lead (mensajes en orden cronológico):\n";
            foreach ($messages as $index => $message) {
                $prompt .= ($index + 1) . ". \"{$message}\"\n";
            }
        }

        $prompt .= "\n\nDetermina la intención basándote en TODA la conversación, no solo el último mensaje.";

        return $prompt;
    }

    /**
     * Prompt del sistema para OpenAI
     */
    protected function getSystemPrompt(): string
    {
        return <<<'PROMPT'
Eres un asistente especializado en analizar intenciones de leads en conversaciones de ventas.

Tu tarea es clasificar la respuesta del lead en una de estas categorías:

1. **interested**: El lead muestra interés, curiosidad, quiere más información, o tiene una actitud positiva.
   Ejemplos: "sí", "me interesa", "cuéntame más", "dale", "ok", "perfecto", "bien gracias", "hola", "bueno", "claro".

2. **not_interested**: El lead rechaza explícitamente, no quiere ser contactado, o muestra desinterés claro.
   Ejemplos: "no", "no me interesa", "no molesten", "bájame", "stop", "no gracias".

3. **neutral**: El mensaje no permite determinar claramente la intención (muy ambiguo o fuera de contexto).
   Ejemplos: mensajes confusos, preguntas no relacionadas, o respuestas que no indican interés ni desinterés.

**Importante:**
- Mensajes corteses o neutrales como "bien gracias", "hola", "bueno" deben considerarse como **interested** (mantener la conversación abierta).
- Solo marca como **not_interested** si hay un rechazo explícito.
- Responde ÚNICAMENTE con una palabra: "interested", "not_interested" o "neutral".
- NO agregues explicaciones ni puntuación adicional.
PROMPT;
    }

    /**
     * Parsear respuesta de OpenAI a valor de intención
     */
    protected function parseIntentionFromResponse(string $response): ?string
    {
        // Limpiar respuesta
        $response = preg_replace('/[^\w\s]/', '', $response);
        $response = trim(strtolower($response));

        return match ($response) {
            'interested', 'interesado', 'yes', 'si', 'sí' => LeadIntention::INTERESTED->value,
            'not_interested', 'notinterested', 'no interesado', 'not interested', 'no' => LeadIntention::NOT_INTERESTED->value,
            default => null, // neutral o no determinado
        };
    }
}
