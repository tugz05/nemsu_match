<?php

namespace App\Listeners;

use App\Events\NotificationSent;
use App\Services\WebPushService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWebPushForNotification implements ShouldQueue
{
    public function __construct(
        protected WebPushService $webPush
    ) {}

    public function handle(NotificationSent $event): void
    {
        $notification = $event->notification;
        if ($notification->type !== 'nearby_heart_tap') {
            $notification->load('fromUser:id,display_name,fullname,profile_picture');
        }

        try {
            $this->webPush->sendForNotification($notification);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
