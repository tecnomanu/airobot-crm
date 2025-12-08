<?php

declare(strict_types=1);

namespace App\Events\Calculator;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando un usuario mueve su cursor
 * (Opcional - para mostrar presencia de usuarios)
 */
class CursorMoved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $calculatorId,
        public string $cellId,
        public int $userId,
        public string $userName,
        public string $userColor
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel("calculator.{$this->calculatorId}");
    }

    public function broadcastAs(): string
    {
        return 'cursor.moved';
    }

    public function broadcastWith(): array
    {
        return [
            'cell_id' => $this->cellId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'user_color' => $this->userColor,
            'timestamp' => now()->toISOString(),
        ];
    }
}

