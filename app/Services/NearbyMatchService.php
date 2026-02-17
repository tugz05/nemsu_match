<?php

namespace App\Services;

use App\Events\MatchProximityUpdated;
use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\User;
use App\Models\AiProximityMatch;
use App\Models\UserMatch;

class NearbyMatchService
{
    public function __construct(
        private ProximityMatchService $proximityMatch
    ) {}
    /** Minimum hours between "nearby match" notifications for the same pair. */
    public const NEARBY_NOTIFICATION_COOLDOWN_HOURS = 24;

    /**
     * Distance in meters between two points (Haversine formula).
     */
    public static function distanceMeters(?float $lat1, ?float $lon1, ?float $lat2, ?float $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $earthRadiusM = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusM * $c;
    }

    /**
     * Update user location and check for nearby mutual matches; send notifications when in range.
     */
    public function updateLocationAndNotify(User $user, float $latitude, float $longitude): void
    {
        $user->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_updated_at' => now(),
        ]);

        // Update AI match proximity in real-time (regardless of nearby_match_enabled)
        $this->broadcastAiMatchProximity($user);

        if ($user->nearby_match_enabled) {
            $this->checkAndNotifyNearbyMatches($user);
        }
    }

    /**
     * Broadcast current proximity between the user and their AI match over Pusher.
     */
    protected function broadcastAiMatchProximity(User $user): void
    {
        $ai = AiProximityMatch::query()
            ->where('user_id', $user->id)
            ->first();

        if (! $ai || ! $ai->matchedUser) {
            return;
        }

        $match = $ai->matchedUser;

        $distance = $this->proximityMatch->distanceToMatchMeters($user, $match);
        $percentage = $this->proximityMatch->matchProximityPercentage($user, $match);
        $isNearby10m = $this->proximityMatch->isNearbyMatch($user, $match, 10);

        broadcast(new MatchProximityUpdated(
            $user,
            $match,
            $distance !== null ? (int) round($distance) : null,
            $percentage,
            $isNearby10m
        ));
    }

    /**
     * Find mutual matches within the user's radius who also have location and feature enabled; send notifications.
     */
    public function checkAndNotifyNearbyMatches(User $user): void
    {
        if (! $user->nearby_match_enabled || $user->latitude === null || $user->longitude === null) {
            return;
        }

        $radiusM = (int) $user->nearby_match_radius_m;
        $radiusM = max(500, min(2000, $radiusM));

        $mutualMatchUserIds = $this->getMutualMatchUserIds($user->id);

        if ($mutualMatchUserIds === []) {
            return;
        }

        $candidates = User::query()
            ->whereIn('id', $mutualMatchUserIds)
            ->where('nearby_match_enabled', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('id', '!=', $user->id)
            ->get(['id', 'latitude', 'longitude', 'nearby_match_radius_m']);

        $cooldown = now()->subHours(self::NEARBY_NOTIFICATION_COOLDOWN_HOURS);

        foreach ($candidates as $other) {
            $distance = self::distanceMeters(
                (float) $user->latitude,
                (float) $user->longitude,
                (float) $other->latitude,
                (float) $other->longitude
            );

            if ($distance === null || $distance > $radiusM) {
                continue;
            }

            $otherRadius = max(500, min(2000, (int) $other->nearby_match_radius_m));
            if ($distance > $otherRadius) {
                continue;
            }

            if ($this->recentNearbyNotificationExists($user->id, $other->id, $cooldown)) {
                continue;
            }

            $this->sendNearbyMatchNotifications($user, $other, (int) round($distance));
        }
    }

    /**
     * Get user IDs that are mutual matches with the given user (from matches table).
     */
    protected function getMutualMatchUserIds(int $userId): array
    {
        $rows = UserMatch::query()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)->orWhere('matched_user_id', $userId);
            })
            ->get(['user_id', 'matched_user_id']);

        $ids = [];
        foreach ($rows as $row) {
            $other = $row->user_id === $userId ? $row->matched_user_id : $row->user_id;
            if ($other !== $userId) {
                $ids[] = $other;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Whether we already sent a nearby_match notification between these two in the cooldown window.
     */
    protected function recentNearbyNotificationExists(int $userId1, int $userId2, \DateTimeInterface $since): bool
    {
        return Notification::query()
            ->where('type', 'nearby_match')
            ->where('created_at', '>=', $since)
            ->where(function ($q) use ($userId1, $userId2) {
                $q->where(function ($q2) use ($userId1, $userId2) {
                    $q2->where('user_id', $userId1)->where('from_user_id', $userId2);
                })->orWhere(function ($q2) use ($userId1, $userId2) {
                    $q2->where('user_id', $userId2)->where('from_user_id', $userId1);
                });
            })
            ->exists();
    }

    /**
     * Create "match is nearby" notifications for both users and broadcast.
     */
    protected function sendNearbyMatchNotifications(User $userA, User $userB, int $distanceM): void
    {
        $distanceText = $distanceM < 1000
            ? "{$distanceM} m away"
            : sprintf('%s km away', number_format($distanceM / 1000, 1));

        $data = ['distance_m' => $distanceM, 'distance_text' => $distanceText];

        $toA = Notification::notify(
            $userA->id,
            'nearby_match',
            $userB->id,
            'user',
            $userB->id,
            $data
        );
        if ($toA) {
            broadcast(new NotificationSent($toA));
        }

        $toB = Notification::notify(
            $userB->id,
            'nearby_match',
            $userA->id,
            'user',
            $userA->id,
            $data
        );
        if ($toB) {
            broadcast(new NotificationSent($toB));
        }
    }
}
