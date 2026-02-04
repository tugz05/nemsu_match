<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Message $message
    ) {
        $this->message->load('sender:id,display_name,fullname,profile_picture');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.'.$this->message->conversation_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        $m = $this->message;

        return [
            'id' => $m->id,
            'conversation_id' => $m->conversation_id,
            'sender_id' => $m->sender_id,
            'sender' => $m->sender ? [
                'id' => $m->sender->id,
                'display_name' => $m->sender->display_name,
                'fullname' => $m->sender->fullname,
                'profile_picture' => $m->sender->profile_picture,
            ] : null,
            'body' => $m->body,
            'read_at' => $m->read_at?->toIso8601String(),
            'created_at' => $m->created_at->toIso8601String(),
        ];
    }
}
