<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Broadcast when someone on a campus updates their location so other users
 * on the same campus can refetch radar (nearby users) in real time via Pusher.
 */
class RadarUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $campusName
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $slug = Str::slug(trim($this->campusName), '-');
        $slug = $slug !== '' ? $slug : 'default';

        return [
            new Channel('campus.'.$slug),
        ];
    }

    public function broadcastAs(): string
    {
        return 'RadarUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'campus' => $this->campusName,
        ];
    }
}
