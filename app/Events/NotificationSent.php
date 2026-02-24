<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Notification $notification
    ) {
        // Do not load fromUser for anonymous types (e.g. nearby_heart_tap) so profile is never sent
        if ($this->notification->type !== 'nearby_heart_tap') {
            $this->notification->load('fromUser:id,display_name,fullname,profile_picture');
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->notification->user_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'NotificationSent';
    }

    /**
     * Get the data to broadcast (same shape as API for the frontend).
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $n = $this->notification;

        return [
            'id' => $n->id,
            'type' => $n->type,
            'from_user_id' => $n->from_user_id,
            'from_user' => $n->type === 'nearby_heart_tap' ? null : ($n->fromUser ? [
                'id' => $n->fromUser->id,
                'display_name' => $n->fromUser->display_name,
                'fullname' => $n->fromUser->fullname,
                'profile_picture' => $n->fromUser->profile_picture,
            ] : null),
            'notifiable_type' => $n->notifiable_type,
            'notifiable_id' => $n->notifiable_id,
            'data' => $n->data,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at->toIso8601String(),
        ];
    }
}
