<?php

declare(strict_types=1);

namespace App\Events\Calculator;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando una celda es actualizada
 */
class CellUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $calculatorId,
        public string $cellId,
        public mixed $value,
        public ?array $format,
        public int $version,
        public int $userId,
        public string $userName
    ) {}

    /**
     * Canal privado donde se transmite el evento
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("calculator.{$this->calculatorId}");
    }

    /**
     * Nombre del evento en el frontend
     */
    public function broadcastAs(): string
    {
        return 'cell.updated';
    }

    /**
     * Datos que se envían al frontend
     */
    public function broadcastWith(): array
    {
        return [
            'cell_id' => $this->cellId,
            'value' => $this->value,
            'format' => $this->format,
            'version' => $this->version,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'timestamp' => now()->toISOString(),
        ];
    }
}

