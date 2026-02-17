<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchProximityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public User $user,
        public User $match,
        public ?int $distanceMeters,
        public ?int $proximityPercentage,
        public bool $isNearby10m
    ) {}

    /**
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->user->id),
            new PrivateChannel('user.'.$this->match->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MatchProximityUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'match_user_id' => $this->match->id,
            'distance_m' => $this->distanceMeters,
            'proximity_percentage' => $this->proximityPercentage,
            'is_nearby_10m' => $this->isNearby10m,
        ];
    }
}

