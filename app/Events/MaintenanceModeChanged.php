<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MaintenanceModeChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $enabled;

    /**
     * Create a new event instance.
     */
    public function __construct(bool $enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('app-status');
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MaintenanceModeChanged';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'maintenance_mode' => $this->enabled,
        ];
    }
}
