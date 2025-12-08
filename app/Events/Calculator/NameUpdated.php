<?php

declare(strict_types=1);

namespace App\Events\Calculator;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento emitido cuando el nombre del calculator cambia
 */
class NameUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $calculatorId,
        public string $name,
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
        return 'name.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'name' => $this->name,
            'version' => $this->version,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'timestamp' => now()->toISOString(),
        ];
    }
}

