<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Exceptions\Business\ValidationException;
use App\Models\Client;
use App\Models\Source;
use App\Models\User;
use App\Services\Source\SourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test para SourceService
 */
class SourceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SourceService $service;

    protected Client $client;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SourceService::class);
        $this->client = Client::factory()->create();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function puede_crear_fuente_whatsapp()
    {
        $data = [
            'name' => 'WhatsApp Test',
            'type' => SourceType::WHATSAPP->value,
            'config' => [
                'instance_name' => 'test_instance',
                'api_url' => 'https://api.example.com',
                'api_key' => 'test_key_123',
            ],
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
        ];

        $source = $this->service->create($data);

        $this->assertInstanceOf(Source::class, $source);
        $this->assertEquals('WhatsApp Test', $source->name);
        $this->assertEquals(SourceType::WHATSAPP, $source->type);
        $this->assertEquals(SourceStatus::PENDING_SETUP, $source->status);
        $this->assertDatabaseHas('sources', [
            'name' => 'WhatsApp Test',
            'type' => 'whatsapp',
        ]);
    }

    /** @test */
    public function puede_crear_fuente_webhook()
    {
        $data = [
            'name' => 'Webhook Test',
            'type' => SourceType::WEBHOOK->value,
            'config' => [
                'url' => 'https://example.com/webhook',
                'method' => 'POST',
                'secret' => 'secret_123',
            ],
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
        ];

        $source = $this->service->create($data);

        $this->assertInstanceOf(Source::class, $source);
        $this->assertEquals('Webhook Test', $source->name);
        $this->assertEquals(SourceType::WEBHOOK, $source->type);
    }

    /** @test */
    public function no_puede_crear_fuente_con_config_incompleta()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Configuración incompleta');

        $data = [
            'name' => 'WhatsApp Incompleto',
            'type' => SourceType::WHATSAPP->value,
            'config' => [
                'instance_name' => 'test_instance',
                // Faltan api_url y api_key
            ],
            'client_id' => $this->client->id,
        ];

        $this->service->create($data);
    }

    /** @test */
    public function no_puede_crear_fuente_con_nombre_duplicado()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Ya existe una fuente con ese nombre');

        // Crear primera fuente
        Source::factory()->create([
            'name' => 'Fuente Duplicada',
            'client_id' => $this->client->id,
        ]);

        // Intentar crear otra con el mismo nombre
        $data = [
            'name' => 'Fuente Duplicada',
            'type' => SourceType::WHATSAPP->value,
            'config' => [
                'instance_name' => 'test',
                'api_url' => 'https://api.example.com',
                'api_key' => 'key123',
            ],
            'client_id' => $this->client->id,
        ];

        $this->service->create($data);
    }

    /** @test */
    public function puede_actualizar_fuente()
    {
        $source = Source::factory()->create([
            'name' => 'Original',
            'type' => SourceType::WHATSAPP->value,
            'client_id' => $this->client->id,
        ]);

        $updated = $this->service->update($source->id, [
            'name' => 'Actualizado',
        ]);

        $this->assertEquals('Actualizado', $updated->name);
        $this->assertDatabaseHas('sources', [
            'id' => $source->id,
            'name' => 'Actualizado',
        ]);
    }

    /** @test */
    public function puede_activar_fuente_con_config_valida()
    {
        $source = Source::factory()->create([
            'type' => SourceType::WHATSAPP->value,
            'config' => [
                'instance_name' => 'test',
                'api_url' => 'https://api.example.com',
                'api_key' => 'key123',
            ],
            'status' => SourceStatus::INACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $activated = $this->service->activate($source->id);

        $this->assertEquals(SourceStatus::ACTIVE, $activated->status);
    }

    /** @test */
    public function no_puede_activar_fuente_sin_config_valida()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('No se puede activar la fuente sin una configuración válida');

        $source = Source::factory()->create([
            'type' => SourceType::WHATSAPP->value,
            'config' => [], // Config vacía
            'status' => SourceStatus::INACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $this->service->activate($source->id);
    }

    /** @test */
    public function puede_desactivar_fuente()
    {
        $source = Source::factory()->create([
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $deactivated = $this->service->deactivate($source->id);

        $this->assertEquals(SourceStatus::INACTIVE, $deactivated->status);
    }

    /** @test */
    public function puede_obtener_fuentes_por_tipo()
    {
        Source::factory()->count(3)->create([
            'type' => SourceType::WHATSAPP->value,
            'client_id' => $this->client->id,
        ]);

        Source::factory()->count(2)->create([
            'type' => SourceType::WEBHOOK->value,
            'client_id' => $this->client->id,
        ]);

        $whatsappSources = $this->service->getByType(SourceType::WHATSAPP);
        $webhookSources = $this->service->getByType(SourceType::WEBHOOK);

        $this->assertCount(3, $whatsappSources);
        $this->assertCount(2, $webhookSources);
    }

    /** @test */
    public function puede_obtener_fuentes_por_cliente()
    {
        $otherClient = Client::factory()->create();

        Source::factory()->count(3)->create(['client_id' => $this->client->id]);
        Source::factory()->count(2)->create(['client_id' => $otherClient->id]);

        $clientSources = $this->service->getByClient($this->client->id);

        $this->assertCount(3, $clientSources);
    }

    /** @test */
    public function puede_obtener_estadisticas()
    {
        Source::factory()->count(2)->create([
            'type' => SourceType::WHATSAPP->value,
            'status' => SourceStatus::ACTIVE->value,
        ]);

        Source::factory()->create([
            'type' => SourceType::WEBHOOK->value,
            'status' => SourceStatus::INACTIVE->value,
        ]);

        $stats = $this->service->getStats();

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(2, $stats['active']);
        $this->assertEquals(2, $stats['by_type']['whatsapp']);
        $this->assertEquals(1, $stats['by_type']['webhook']);
    }

    /** @test */
    public function valida_url_invalida_en_webhook()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('URL de webhook inválida');

        $data = [
            'name' => 'Webhook Inválido',
            'type' => SourceType::WEBHOOK->value,
            'config' => [
                'url' => 'not-a-valid-url',
                'method' => 'POST',
                'secret' => 'secret_123',
            ],
            'client_id' => $this->client->id,
        ];

        $this->service->create($data);
    }

    /** @test */
    public function valida_metodo_http_invalido_en_webhook()
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Método HTTP inválido');

        $data = [
            'name' => 'Webhook Inválido',
            'type' => SourceType::WEBHOOK->value,
            'config' => [
                'url' => 'https://example.com/webhook',
                'method' => 'INVALID',
                'secret' => 'secret_123',
            ],
            'client_id' => $this->client->id,
        ];

        $this->service->create($data);
    }
}
