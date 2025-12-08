<?php

declare(strict_types=1);

namespace App\Events\Calculator;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando la altura de una fila cambia
 */
class RowResized implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $calculatorId,
        public int $row,
        public int $height,
        public int $version,
        public int $userId,
        public string $userName
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("calculator.{$this->calculatorId}");
    }

    public function broadcastAs(): string
    {
        return 'row.resized';
    }

    public function broadcastWith(): array
    {
        return [
            'row' => $this->row,
            'height' => $this->height,
            'version' => $this->version,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'timestamp' => now()->toISOString(),
        ];
    }
}

