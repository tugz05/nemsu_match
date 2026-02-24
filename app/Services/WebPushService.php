<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\PushSubscription;
use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    protected ?WebPush $webPush = null;

    protected function getWebPush(): WebPush
    {
        if ($this->webPush !== null) {
            return $this->webPush;
        }

        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');
        $subject = config('webpush.vapid.subject');

        if (! $publicKey || ! $privateKey) {
            throw new \RuntimeException('VAPID keys are not configured. Set VAPID_PUBLIC_KEY and VAPID_PRIVATE_KEY in .env');
        }

        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ], [
            'TTL' => 3600,
            'urgency' => 'normal',
        ]);

        return $this->webPush;
    }

    /**
     * Get title and body for a notification type (mirrors frontend getNotificationTitleAndBody).
     */
    public static function titleAndBodyForNotification(Notification $n): array
    {
        $name = 'Someone';
        if ($n->type !== 'nearby_heart_tap' && $n->fromUser) {
            $name = $n->fromUser->display_name ?? $n->fromUser->fullname ?? $name;
        }
        $d = $n->data ?? [];

        return match ($n->type) {
            'message' => [
                'title' => $name,
                'body' => (string) ($d['excerpt'] ?? 'New message'),
            ],
            'comment' => ['title' => 'New comment', 'body' => "{$name} commented on your post"],
            'like' => ['title' => 'New like', 'body' => "{$name} liked your post"],
            'follow' => ['title' => 'New follower', 'body' => "{$name} started following you"],
            'comment_like' => ['title' => 'Comment liked', 'body' => "{$name} liked your comment"],
            'message_request' => ['title' => 'Message request', 'body' => "{$name} wants to message you"],
            'message_request_accepted' => ['title' => 'Request accepted', 'body' => "{$name} accepted your message request"],
            'match_dating' => ['title' => 'Heart match', 'body' => "{$name} sent you a heart match"],
            'match_friend' => ['title' => 'Smile match', 'body' => "{$name} sent you a smile match"],
            'match_study_buddy' => ['title' => 'Study buddy', 'body' => "{$name} wants to be your study buddy"],
            'mutual_match' => ['title' => "It's a match!", 'body' => "You and {$name} matched"],
            'high_compatibility_match' => [
                'title' => 'High match',
                'body' => sprintf('%s has you as a %s match!', $name, isset($d['compatibility_score']) ? $d['compatibility_score'].'%' : '70%+'),
            ],
            'nearby_match' => ['title' => 'Match nearby', 'body' => "Hey! {$name} is nearby—say hi or plan to meet up!"],
            'nearby_heart_tap' => ['title' => 'Someone tapped your heart', 'body' => 'Open Find Your Match to tap back.'],
            'test' => ['title' => 'NEMSU Match - Test', 'body' => 'This is a test notification from Pusher. Browser notifications are working.'],
            default => [
                'title' => 'NEMSU Match',
                'body' => $n->from_user_id ? "{$name} sent you a notification" : (isset($d['excerpt']) ? (string) $d['excerpt'] : 'You have a new notification'),
            ],
        };
    }

    /**
     * Build URL to open when user clicks the push notification.
     */
    public static function urlForNotification(Notification $n): string
    {
        $base = rtrim(config('app.url'), '/');
        $convId = $n->notifiable_type === 'conversation' ? $n->notifiable_id : ($n->data['conversation_id'] ?? null);

        if (in_array($n->type, ['message', 'message_request', 'message_request_accepted']) && $convId) {
            return "{$base}/chat?conversation={$convId}";
        }
        if (in_array($n->type, ['mutual_match', 'nearby_match'])) {
            return "{$base}/like-you?tab=matches&show_match={$n->from_user_id}";
        }
        if (in_array($n->type, ['match_dating', 'match_friend', 'match_study_buddy'])) {
            return "{$base}/like-you?tab=match_back";
        }
        if ($n->type === 'follow') {
            return "{$base}/profile/{$n->from_user_id}";
        }
        if ($n->type === 'nearby_heart_tap') {
            return "{$base}/find-your-match?show_tap_back=1";
        }

        return "{$base}/notifications";
    }

    /**
     * Send Web Push for a single in-app notification to all of the user's subscriptions.
     */
    public function sendForNotification(Notification $notification): void
    {
        $subscriptions = PushSubscription::where('user_id', $notification->user_id)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        [$title, $body] = array_values(self::titleAndBodyForNotification($notification));
        $url = self::urlForNotification($notification);

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'url' => $url,
            'id' => $notification->id,
            'type' => $notification->type,
        ]);

        try {
            $webPush = $this->getWebPush();
            foreach ($subscriptions as $sub) {
                try {
                    $subscription = Subscription::create([
                        'endpoint' => $sub->endpoint,
                        'keys' => [
                            'p256dh' => $sub->public_key,
                            'auth' => $sub->auth_token,
                        ],
                    ]);
                    $webPush->queueNotification($subscription, $payload);
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            $reports = $webPush->flush();
            foreach ($reports as $report) {
                if (! $report->isSuccess() && $report->isSubscriptionExpired()) {
                    PushSubscription::where('endpoint', $report->getEndpoint())->delete();
                }
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
