<?php

declare(strict_types=1);

namespace App\Events\Calculator;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando se inserta una columna
 */
class ColumnInserted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $calculatorId,
        public string $column,
        public string $position, // 'before' | 'after'
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
        return 'column.inserted';
    }

    public function broadcastWith(): array
    {
        return [
            'column' => $this->column,
            'position' => $this->position,
            'version' => $this->version,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'timestamp' => now()->toISOString(),
        ];
    }
}

