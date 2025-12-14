<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\SourceStatus;
use App\Enums\SourceType;
use App\Exceptions\Business\ValidationException;
use App\Models\Client\Client;
use App\Models\Integration\Source;
use App\Models\User;
use App\Services\Source\SourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function can_create_whatsapp_source()
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

    #[Test]
    public function can_create_webhook_source()
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

    #[Test]
    public function cannot_create_source_with_incomplete_config()
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

    #[Test]
    public function cannot_create_source_with_duplicate_name()
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

    #[Test]
    public function can_update_source()
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

    #[Test]
    public function can_activate_source_with_valid_config()
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

    #[Test]
    public function cannot_activate_source_without_valid_config()
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

    #[Test]
    public function can_deactivate_source()
    {
        $source = Source::factory()->create([
            'status' => SourceStatus::ACTIVE->value,
            'client_id' => $this->client->id,
        ]);

        $deactivated = $this->service->deactivate($source->id);

        $this->assertEquals(SourceStatus::INACTIVE, $deactivated->status);
    }

    #[Test]
    public function can_get_sources_by_type()
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

    #[Test]
    public function can_get_sources_by_client()
    {
        $otherClient = Client::factory()->create();

        Source::factory()->count(3)->create(['client_id' => $this->client->id]);
        Source::factory()->count(2)->create(['client_id' => $otherClient->id]);

        $clientSources = $this->service->getByClient($this->client->id);

        $this->assertCount(3, $clientSources);
    }

    #[Test]
    public function can_get_statistics()
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

    #[Test]
    public function validates_invalid_url_in_webhook()
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

    #[Test]
    public function validates_invalid_http_method_in_webhook()
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
