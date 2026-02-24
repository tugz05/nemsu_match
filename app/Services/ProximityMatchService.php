<?php

namespace App\Services;

use App\Models\AiProximityMatch;
use App\Models\AnonymousChatRoom;
use App\Models\Campus;
use App\Models\NearbyTap;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class ProximityMatchService
{
    /** Distance (meters) at which user is considered "at" campus base (100% = proximity match). */
    public const PROXIMITY_RADIUS_M = 1;

    /** Max distance (meters) for percentage scale: beyond this = 0%. */
    public const MAX_DISTANCE_FOR_PERCENTAGE_M = 500;

    /** Radius (meters) from campus base for radar "nearest users" list. */
    public const RADAR_RADIUS_M = 500;

    /** Consider user "active" for nearby if last_seen_at is within this many minutes. */
    public const ACTIVE_NEARBY_MINUTES = 15;

    /**
     * Get campus model by name (matches users.campus string).
     */
    public function getCampusByUserCampus(?string $campusName): ?Campus
    {
        if ($campusName === null || trim($campusName) === '') {
            return null;
        }
        $campusName = trim($campusName);
        return Campus::query()
            ->where('name', $campusName)
            ->orWhere('code', $campusName)
            ->first();
    }

    /**
     * Get or assign an AI proximity match for the user (same campus only).
     * Uses OpenAI to pick the best match when OPENAI_API_KEY is set; otherwise uses heuristic scoring.
     *
     * @param  array<string, mixed>|null  $debug  Filled with debug info about how the match was chosen (for logging / dev tools).
     */
    public function getOrAssignMatch(User $user, ?array &$debug = null): ?User
    {
        $debug = $debug ?? [];

        // Use the campus column on users table for matching (string-based),
        // and only rely on Campus model when we actually need base lat/long
        // for distance/proximity. This way AI matching still works even if
        // Superadmin hasn't fully configured campus records yet.
        $campusName = $user->campus;
        if ($campusName === null || trim($campusName) === '') {
            $debug['reason'] = 'no_user_campus';
            return null;
        }

        $existing = AiProximityMatch::where('user_id', $user->id)->first();
        if ($existing) {
            $match = $existing->matchedUser;
            if ($match && ! $this->isUserEligible($match)) {
                $existing->delete();
            } else {
                $debug['source'] = 'existing_assignment';
                $debug['matched_user_id'] = $match?->id;
                return $match;
            }
        }

        $candidates = $this->getSameCampusCandidates($user);
        if ($candidates->isEmpty()) {
            $debug['reason'] = 'no_candidates';
            return null;
        }

        $picked = $this->pickBestMatch($user, $candidates, $debug);
        if (! $picked) {
            $debug['reason'] = 'no_pick';
            return null;
        }

        AiProximityMatch::updateOrCreate(
            ['user_id' => $user->id],
            ['matched_user_id' => $picked->id, 'assigned_at' => now()]
        );

        return $picked;
    }

    public function resetMatch(User $user): void
    {
        AiProximityMatch::where('user_id', $user->id)->delete();
    }

    /**
     * Distance in meters from user's current location to their campus base.
     */
    public function distanceToCampusBaseMeters(User $user): ?float
    {
        $campus = $this->getCampusByUserCampus($user->campus);
        if (! $campus || ! $campus->hasBaseLocation() || $user->latitude === null || $user->longitude === null) {
            return null;
        }
        return NearbyMatchService::distanceMeters(
            (float) $user->latitude,
            (float) $user->longitude,
            (float) $campus->base_latitude,
            (float) $campus->base_longitude
        );
    }

    /**
     * Distance in meters between the user and their matched user.
     */
    public function distanceToMatchMeters(User $user, User $match): ?float
    {
        return NearbyMatchService::distanceMeters(
            $user->latitude !== null ? (float) $user->latitude : null,
            $user->longitude !== null ? (float) $user->longitude : null,
            $match->latitude !== null ? (float) $match->latitude : null,
            $match->longitude !== null ? (float) $match->longitude : null,
        );
    }

    /**
     * Percentage (0-100) of how close the user is to campus base.
     * 100% = within PROXIMITY_RADIUS_M, 0% = at or beyond MAX_DISTANCE_FOR_PERCENTAGE_M.
     */
    public function locationPercentage(User $user): ?int
    {
        $distance = $this->distanceToCampusBaseMeters($user);
        if ($distance === null) {
            return null;
        }
        if ($distance <= self::PROXIMITY_RADIUS_M) {
            return 100;
        }
        if ($distance >= self::MAX_DISTANCE_FOR_PERCENTAGE_M) {
            return 0;
        }
        $range = self::MAX_DISTANCE_FOR_PERCENTAGE_M - self::PROXIMITY_RADIUS_M;
        $pct = 100 - (($distance - self::PROXIMITY_RADIUS_M) / $range) * 100;
        return (int) round(max(0, min(100, $pct)));
    }

    /**
     * Percentage (0-100) of how close the user is to their match.
     * 100% = at the same point, 0% = at or beyond MAX_DISTANCE_FOR_PERCENTAGE_M.
     */
    public function matchProximityPercentage(User $user, User $match): ?int
    {
        $distance = $this->distanceToMatchMeters($user, $match);
        if ($distance === null) {
            return null;
        }
        if ($distance <= 0.5) {
            return 100;
        }
        if ($distance >= self::MAX_DISTANCE_FOR_PERCENTAGE_M) {
            return 0;
        }
        $pct = 100 - ($distance / self::MAX_DISTANCE_FOR_PERCENTAGE_M) * 100;
        return (int) round(max(0, min(100, $pct)));
    }

    /**
     * True if both user and matched user are within 1m of the same campus base.
     */
    public function isProximityMatch(User $user, User $matchedUser): bool
    {
        $campus = $this->getCampusByUserCampus($user->campus);
        if (! $campus || ! $campus->hasBaseLocation()) {
            return false;
        }
        if ($user->campus !== $matchedUser->campus) {
            return false;
        }
        $baseLat = (float) $campus->base_latitude;
        $baseLon = (float) $campus->base_longitude;
        $userDist = NearbyMatchService::distanceMeters(
            (float) $user->latitude,
            (float) $user->longitude,
            $baseLat,
            $baseLon
        );
        $matchDist = NearbyMatchService::distanceMeters(
            (float) $matchedUser->latitude,
            (float) $matchedUser->longitude,
            $baseLat,
            $baseLon
        );
        if ($userDist === null || $matchDist === null) {
            return false;
        }
        return $userDist <= self::PROXIMITY_RADIUS_M && $matchDist <= self::PROXIMITY_RADIUS_M;
    }

    /**
     * True if user and matched user are within a given radius (default 10m) of each other.
     */
    public function isNearbyMatch(User $user, User $matchedUser, float $radiusMeters = 10): bool
    {
        $distance = $this->distanceToMatchMeters($user, $matchedUser);
        if ($distance === null) {
            return false;
        }
        return $distance <= $radiusMeters;
    }

    /**
     * Radar data: campus base, current user position relative to base, and nearest same-campus users
     * within RADAR_RADIUS_M of the base (each with distance/bearing from base, distance from me).
     * Used to render a radar with heart blips for nearest users around the campus base point.
     *
     * @return array{campus_base: array{latitude: float, longitude: float}|null, radar_radius_m: int, me: array{distance_from_base_m: float, bearing_from_base: float}|null, nearby_users: array<int, array{id: int, display_name: string, profile_picture: string|null, distance_from_base_m: float, bearing_from_base: float, distance_from_me_m: float|null}>}
     */
    public function getRadarData(User $user): array
    {
        $campus = $this->getCampusByUserCampus($user->campus);
        $baseLat = $campus && $campus->hasBaseLocation() ? (float) $campus->base_latitude : null;
        $baseLon = $campus && $campus->hasBaseLocation() ? (float) $campus->base_longitude : null;

        $result = [
            'campus_base' => null,
            'radar_radius_m' => self::RADAR_RADIUS_M,
            'me' => null,
            'nearby_users' => [],
        ];

        if ($baseLat === null || $baseLon === null) {
            return $result;
        }

        $result['campus_base'] = ['latitude' => $baseLat, 'longitude' => $baseLon];

        $myLat = $user->latitude !== null ? (float) $user->latitude : null;
        $myLon = $user->longitude !== null ? (float) $user->longitude : null;
        if ($myLat !== null && $myLon !== null) {
            $myDist = NearbyMatchService::distanceMeters($baseLat, $baseLon, $myLat, $myLon);
            $myBearing = NearbyMatchService::bearingDegrees($baseLat, $baseLon, $myLat, $myLon);
            if ($myDist !== null) {
                $result['me'] = [
                    'distance_from_base_m' => round($myDist, 1),
                    'bearing_from_base' => round($myBearing, 2),
                ];
            }
        }

        $candidates = $this->getSameCampusCandidates($user);
        $radiusM = self::RADAR_RADIUS_M;
        $list = [];
        foreach ($candidates as $other) {
            $olat = $other->latitude !== null ? (float) $other->latitude : null;
            $olon = $other->longitude !== null ? (float) $other->longitude : null;
            if ($olat === null || $olon === null) {
                continue;
            }
            $distFromBase = NearbyMatchService::distanceMeters($baseLat, $baseLon, $olat, $olon);
            if ($distFromBase === null || $distFromBase > $radiusM) {
                continue;
            }
            $bearing = NearbyMatchService::bearingDegrees($baseLat, $baseLon, $olat, $olon);
            $distFromMe = $this->distanceToMatchMeters($user, $other);
            $list[] = [
                'id' => $other->id,
                'display_name' => $other->display_name ?? '',
                'profile_picture' => $other->profile_picture,
                'distance_from_base_m' => round($distFromBase, 1),
                'bearing_from_base' => round($bearing, 2),
                'distance_from_me_m' => $distFromMe !== null ? round($distFromMe, 1) : null,
            ];
        }
        usort($list, fn ($a, $b) => $a['distance_from_base_m'] <=> $b['distance_from_base_m']);
        $result['nearby_users'] = array_values($list);

        return $result;
    }

    /**
     * Find Your Match: count of nearby users (same campus, preferred_gender, active, within radius).
     * These are the hearts shown outside the circle (tap to notify).
     */
    public function getLikersWithin10mCount(User $user): int
    {
        return count($this->getNearbyUsersWithPosition($user));
    }

    /**
     * Count of distinct users who have tapped you (from nearby_taps table). Shown inside the central circle.
     */
    public function getTappedYouCount(User $user): int
    {
        return NearbyTap::query()
            ->where('target_user_id', $user->id)
            ->select('user_id')
            ->groupBy('user_id')
            ->get()
            ->count();
    }

    /**
     * List of tappers (users who tapped you) that you can "tap back". Returns tokens; only includes
     * tappers with whom you don't already have an anonymous chat room (so tap back can create the room).
     *
     * @return list<array{token: string}>
     */
    public function getTappersForTapBack(User $viewer): array
    {
        $tapperIds = NearbyTap::query()
            ->where('target_user_id', $viewer->id)
            ->pluck('user_id')
            ->unique()
            ->all();

        $roomUserPairs = AnonymousChatRoom::query()
            ->where(function ($q) use ($viewer) {
                $q->where('user1_id', $viewer->id)->orWhere('user2_id', $viewer->id);
            })
            ->get(['user1_id', 'user2_id']);

        $alreadyInRoom = [];
        foreach ($roomUserPairs as $room) {
            $other = $room->user1_id === $viewer->id ? $room->user2_id : $room->user1_id;
            $alreadyInRoom[$other] = true;
        }

        $result = [];
        $exp = now()->addMinutes(15)->timestamp;
        foreach ($tapperIds as $tapperId) {
            if (isset($alreadyInRoom[$tapperId])) {
                continue;
            }
            $payload = [
                'tapper_id' => $tapperId,
                'vid' => $viewer->id,
                'exp' => $exp,
            ];
            $result[] = ['token' => Crypt::encryptString(json_encode($payload))];
        }

        return $result;
    }

    /**
     * Decode tap-back token and return tapper user ID if valid. Token must have vid = viewer->id.
     */
    public function decodeTapBackToken(string $token, User $viewer): ?int
    {
        try {
            $json = Crypt::decryptString($token);
            $payload = json_decode($json, true);
            if (! is_array($payload) || ! isset($payload['tapper_id'], $payload['vid'], $payload['exp'])) {
                return null;
            }
            if ((int) $payload['vid'] !== (int) $viewer->id) {
                return null;
            }
            if ((int) $payload['exp'] < time()) {
                return null;
            }

            return (int) $payload['tapper_id'];
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Record tap-back: viewer taps back tapper. Creates NearbyTap(viewer, tapper). If mutual, creates/gets AnonymousChatRoom.
     *
     * @return array{mutual: bool, room_id?: int}
     */
    public function tapBack(User $viewer, int $tapperId): array
    {
        if ($viewer->id === $tapperId) {
            return ['mutual' => false];
        }

        NearbyTap::firstOrCreate(
            ['user_id' => $viewer->id, 'target_user_id' => $tapperId],
            ['user_id' => $viewer->id, 'target_user_id' => $tapperId]
        );

        if (! NearbyTap::areMutual($viewer->id, $tapperId)) {
            return ['mutual' => false];
        }

        $room = AnonymousChatRoom::getOrCreateForPair($viewer->id, $tapperId);

        return ['mutual' => true, 'room_id' => $room->id];
    }

    /**
     * All nearby users by location only (no prior like required). Same campus, preferred_gender, active, within radius.
     * Bearing from viewer to user (0=North, 90=East). Used for Find Your Match hearts; tap = notify that user.
     *
     * @return list<array{id: int, distance_from_me_m: float, bearing_deg: float}>
     */
    public function getNearbyUsersWithPosition(User $viewer): array
    {
        if ($viewer->latitude === null || $viewer->longitude === null) {
            return [];
        }

        $campusName = $viewer->campus;
        if ($campusName === null || trim($campusName) === '') {
            return [];
        }

        $campusNormalized = strtolower(trim($campusName));
        $activeSince = now()->subMinutes(self::ACTIVE_NEARBY_MINUTES);
        $candidates = User::query()
            ->where('id', '!=', $viewer->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where(function ($q) use ($activeSince) {
                $q->where('last_seen_at', '>=', $activeSince)
                    ->orWhere('location_updated_at', '>=', $activeSince);
            })
            ->get(['id', 'latitude', 'longitude', 'campus', 'gender']);

        $preferredGender = $viewer->preferred_gender ? trim((string) $viewer->preferred_gender) : null;
        $myLat = (float) $viewer->latitude;
        $myLon = (float) $viewer->longitude;
        /** @var float Radius in meters */
        $radiusM = 15.0;

        // Exclude users who already mutually tapped (have an anonymous chat room with viewer)
        $alreadyInRoomIds = AnonymousChatRoom::query()
            ->where(function ($q) use ($viewer) {
                $q->where('user1_id', $viewer->id)->orWhere('user2_id', $viewer->id);
            })
            ->get(['user1_id', 'user2_id'])
            ->map(fn ($room) => $room->user1_id === $viewer->id ? $room->user2_id : $room->user1_id)
            ->flip()
            ->all();

        $nearby = [];
        foreach ($candidates as $other) {
            if (isset($alreadyInRoomIds[$other->id])) {
                continue;
            }
            if (strtolower(trim((string) $other->campus)) !== $campusNormalized) {
                continue;
            }
            if ($preferredGender !== null && $preferredGender !== '' && trim((string) $other->gender) !== $preferredGender) {
                continue;
            }
            $otherLat = trim((string) $other->latitude);
            $otherLon = trim((string) $other->longitude);
            if ($otherLat === '' || $otherLon === '') {
                continue;
            }
            $lat2 = (float) $other->latitude;
            $lon2 = (float) $other->longitude;
            $dist = NearbyMatchService::distanceMeters($myLat, $myLon, $lat2, $lon2);
            if ($dist !== null && $dist <= $radiusM) {
                $bearing = NearbyMatchService::bearingDegrees($myLat, $myLon, $lat2, $lon2);
                $nearby[] = [
                    'id' => $other->id,
                    'distance_from_me_m' => round($dist, 2),
                    'bearing_deg' => round($bearing, 1),
                ];
            }
        }

        return $nearby;
    }

    /**
     * User IDs of everyone within radius of the given user (same campus, has location). Used to broadcast count updates.
     *
     * @return list<int>
     */
    public function getUserIdsWithinRadiusOf(User $centerUser, float $radiusM = 15.0): array
    {
        if ($centerUser->latitude === null || $centerUser->longitude === null) {
            return [];
        }
        $campusName = $centerUser->campus;
        if ($campusName === null || trim($campusName) === '') {
            return [];
        }
        $campusNormalized = strtolower(trim($campusName));
        $others = User::query()
            ->where('id', '!=', $centerUser->id)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'latitude', 'longitude', 'campus']);
        $myLat = (float) $centerUser->latitude;
        $myLon = (float) $centerUser->longitude;
        $ids = [];
        foreach ($others as $other) {
            if (strtolower(trim((string) $other->campus)) !== $campusNormalized) {
                continue;
            }
            $lat2 = (float) $other->latitude;
            $lon2 = (float) $other->longitude;
            $dist = NearbyMatchService::distanceMeters($myLat, $myLon, $lat2, $lon2);
            if ($dist !== null && $dist <= $radiusM) {
                $ids[] = $other->id;
            }
        }
        return $ids;
    }

    /**
     * Return list of nearby hearts with token, position, and whether the viewer already tapped this user.
     *
     * @return list<array{token: string, distance_from_me_m: float, bearing_deg: float, already_tapped_by_me: bool}>
     */
    public function getNearbyLikersWithTokens(User $viewer): array
    {
        $withPosition = $this->getNearbyUsersWithPosition($viewer);
        $result = [];
        $exp = now()->addMinutes(10)->timestamp;
        foreach ($withPosition as $n) {
            $alreadyTapped = NearbyTap::query()
                ->where('user_id', $viewer->id)
                ->where('target_user_id', $n['id'])
                ->exists();
            $payload = [
                'lid' => $n['id'],
                'vid' => $viewer->id,
                'exp' => $exp,
            ];
            $result[] = [
                'token' => Crypt::encryptString(json_encode($payload)),
                'distance_from_me_m' => $n['distance_from_me_m'],
                'bearing_deg' => $n['bearing_deg'],
                'already_tapped_by_me' => $alreadyTapped,
            ];
        }
        return $result;
    }

    /**
     * Debug info: viewer location and all same-campus candidates with lat/long and why included or excluded.
     *
     * @return array{viewer: array, active_since: string, radius_m: float, nearby_candidates: list<array>}
     */
    public function getProximityDebugInfo(User $viewer): array
    {
        $activeSince = now()->subMinutes(self::ACTIVE_NEARBY_MINUTES);
        $radiusM = 15.0;
        $campusName = $viewer->campus;
        $campusNormalized = $campusName !== null && trim($campusName) !== '' ? strtolower(trim($campusName)) : null;
        $preferredGender = $viewer->preferred_gender ? trim((string) $viewer->preferred_gender) : null;
        $myLat = $viewer->latitude !== null ? (float) $viewer->latitude : null;
        $myLon = $viewer->longitude !== null ? (float) $viewer->longitude : null;

        $candidatesRaw = $campusNormalized !== null
            ? User::query()
                ->where('id', '!=', $viewer->id)
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNormalized])
                ->get(['id', 'latitude', 'longitude', 'campus', 'gender', 'last_seen_at', 'location_updated_at'])
            : collect([]);

        $nearbyCandidates = [];
        foreach ($candidatesRaw as $other) {
            $sameCampus = strtolower(trim((string) $other->campus)) === $campusNormalized;
            if (! $sameCampus) {
                continue;
            }
            $lat = $other->latitude !== null && trim((string) $other->latitude) !== '' ? (float) $other->latitude : null;
            $lon = $other->longitude !== null && trim((string) $other->longitude) !== '' ? (float) $other->longitude : null;
            $genderMatch = $preferredGender === null || $preferredGender === '' || trim((string) $other->gender) === $preferredGender;
            $lastSeen = $other->last_seen_at;
            $locUpdated = $other->location_updated_at;
            $active = ($lastSeen && $lastSeen->gte($activeSince)) || ($locUpdated && $locUpdated->gte($activeSince));
            $distance = null;
            if ($myLat !== null && $myLon !== null && $lat !== null && $lon !== null) {
                $distance = NearbyMatchService::distanceMeters($myLat, $myLon, $lat, $lon);
            }
            $distanceOk = $distance !== null && $distance <= $radiusM;
            $included = $genderMatch && $active && $distanceOk;
            $reasons = [];
            if (! $genderMatch) {
                $reasons[] = 'gender_mismatch';
            }
            if (! $active) {
                $reasons[] = 'not_active';
            }
            if (! $distanceOk) {
                $reasons[] = $distance === null ? 'no_distance' : 'distance_' . round($distance, 2) . 'm';
            }
            $nearbyCandidates[] = [
                'id' => $other->id,
                'lat' => $lat,
                'lon' => $lon,
                'campus' => (string) $other->campus,
                'gender' => $other->gender,
                'distance_m' => $distance,
                'gender_match' => $genderMatch,
                'active' => $active,
                'distance_ok' => $distanceOk,
                'included' => $included,
                'reason' => $included ? 'ok' : implode(', ', $reasons),
            ];
        }

        return [
            'viewer' => [
                'user_id' => $viewer->id,
                'lat' => $myLat,
                'lon' => $myLon,
                'campus' => $viewer->campus,
                'preferred_gender' => $viewer->preferred_gender,
            ],
            'active_since' => $activeSince->toIso8601String(),
            'radius_m' => $radiusM,
            'nearby_candidates' => $nearbyCandidates,
            'likers' => $nearbyCandidates,
        ];
    }

    /**
     * Record that viewer tapped target (e.g. when tapping a heart). Used for tap-back and mutual anonymous chat.
     */
    public function recordTap(User $viewer, int $targetUserId): void
    {
        if ($viewer->id === $targetUserId) {
            return;
        }
        NearbyTap::firstOrCreate(
            ['user_id' => $viewer->id, 'target_user_id' => $targetUserId],
            ['user_id' => $viewer->id, 'target_user_id' => $targetUserId]
        );
    }

    /**
     * Decode a tap token and return the target user ID (whose heart was tapped) if valid for this viewer.
     *
     * @return int|null Target user ID to notify, or null if invalid/expired
     */
    public function decodeNearbyTapToken(string $token, User $viewer): ?int
    {
        try {
            $json = Crypt::decryptString($token);
            $payload = json_decode($json, true);
            if (! is_array($payload) || ! isset($payload['lid'], $payload['vid'], $payload['exp'])) {
                return null;
            }
            if ((int) $payload['vid'] !== (int) $viewer->id) {
                return null;
            }
            if ((int) $payload['exp'] < time()) {
                return null;
            }
            return (int) $payload['lid'];
        } catch (\Throwable) {
            return null;
        }
    }

    private function isUserEligible(User $u): bool
    {
        if ($u->is_disabled ?? false) {
            return false;
        }
        if (! $u->profile_completed) {
            return false;
        }
        return true;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, User> */
    private function getSameCampusCandidates(User $user)
    {
        $campusName = $user->campus;
        if (! $campusName) {
            return collect([]);
        }
        return User::query()
            ->where('id', '!=', $user->id)
            ->where('campus', $campusName)
            ->where('profile_completed', true)
            ->where(function ($q) {
                $q->where('is_disabled', false)->orWhereNull('is_disabled');
            })
            ->get(['id', 'display_name', 'profile_picture', 'campus', 'interests', 'bio', 'academic_program', 'latitude', 'longitude']);
    }

    /**
     * Pick best match: try OpenAI when configured, then fall back to heuristic scoring.
     *
     * @param  array<string, mixed>|null  $debug
     */
    private function pickBestMatch(User $user, $candidates, ?array &$debug = null): ?User
    {
        $debug = $debug ?? [];

        $apiKey = config('openai.api_key');
        if (is_string($apiKey) && $apiKey !== '') {
            try {
                $picked = $this->pickBestMatchWithOpenAI($user, $candidates, $debug);
                if ($picked !== null) {
                    $debug['source'] = 'openai';
                    $debug['openai_used'] = true;
                    return $picked;
                }
            } catch (\Throwable $e) {
                Log::warning('ProximityMatchService: OpenAI match selection failed, using heuristic.', [
                    'user_id' => $user->id,
                    'message' => $e->getMessage(),
                ]);
                $debug['openai_error'] = $e->getMessage();
            }
        }

        $debug['source'] = $debug['source'] ?? 'heuristic';
        $debug['openai_used'] = $debug['openai_used'] ?? false;

        return $this->pickBestMatchHeuristic($user, $candidates, $debug);
    }

    /**
     * Use OpenAI to pick the single best match from candidates (same campus).
     * Prompt asks for a 1-based index; invalid or missing response falls back to null.
     * @param  array<string, mixed>|null  $debug
     */
    private function pickBestMatchWithOpenAI(User $user, $candidates, ?array &$debug = null): ?User
    {
        $candidatesArray = $candidates->values()->all();
        if ($candidatesArray === []) {
            return null;
        }

        $userSummary = $this->profileSummary($user);
        $candidatesText = [];
        foreach ($candidatesArray as $i => $c) {
            $oneBased = $i + 1;
            $candidatesText[] = "Candidate {$oneBased}: " . $this->profileSummary($c);
        }

        $systemPrompt = 'You are a matchmaking assistant for a campus dating app. Given one user profile and a list of candidate profiles (all from the same campus), choose the single best match for compatibility (shared interests, similar goals, complementary personality). Reply with ONLY the 1-based index of your chosen candidate as a single number (e.g. 1 or 2). No explanation.';
        $userPrompt = "User looking for a match:\n{$userSummary}\n\nCandidates:\n" . implode("\n\n", $candidatesText) . "\n\nReply with only the 1-based index of the best match (e.g. 1).";

        $model = config('openai.match_model', 'gpt-4o-mini');
        $response = OpenAI::chat()->create([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'max_tokens' => 10,
            'temperature' => 0.3,
        ]);

        $content = $response->choices[0]->message->content ?? null;
        if ($content === null || $content === '') {
            return null;
        }

        $index = $this->parseOneBasedIndex(trim($content), count($candidatesArray));
        if ($index === null) {
            return null;
        }

        // Add debug info for console / logs
        $debug ??= [];
        $debug['openai'] = [
            'model' => $model,
            'raw' => $content,
            'picked_index_1_based' => $index + 1,
        ];

        return $candidatesArray[$index] ?? null;
    }

    private function profileSummary(User $user): string
    {
        $parts = [];
        if ($user->bio) {
            $parts[] = 'Bio: ' . $this->truncate((string) $user->bio, 200);
        }
        $interests = $this->normalizeTags($user->interests);
        if ($interests !== []) {
            $parts[] = 'Interests: ' . implode(', ', array_slice($interests, 0, 15));
        }
        if ($user->academic_program) {
            $parts[] = 'Program: ' . $user->academic_program;
        }
        return $parts === [] ? '(no profile details)' : implode('. ', $parts);
    }

    private function truncate(string $s, int $maxLen): string
    {
        $s = preg_replace('/\s+/', ' ', $s);
        if (mb_strlen($s) <= $maxLen) {
            return $s;
        }
        return mb_substr($s, 0, $maxLen - 3) . '...';
    }

    /**
     * Parse a 1-based index from model output; return 0-based index or null.
     */
    private function parseOneBasedIndex(string $content, int $count): ?int
    {
        if ($count < 1) {
            return null;
        }
        if (preg_match('/\b([1-9]\d*)\b/', $content, $m)) {
            $oneBased = (int) $m[1];
            if ($oneBased >= 1 && $oneBased <= $count) {
                return $oneBased - 1;
            }
        }
        return null;
    }

    /**
     * Pick best match using heuristic: shared interests and same academic program.
     *
     * @param  array<string, mixed>|null  $debug
     */
    private function pickBestMatchHeuristic(User $user, $candidates, ?array &$debug = null): ?User
    {
        $myInterests = $this->normalizeTags($user->interests);
        $best = null;
        $bestScore = -1;
        foreach ($candidates as $c) {
            $score = $this->simpleCompatibilityScore($user, $c, $myInterests);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $c;
            }
        }
        if ($debug !== null && $best) {
            $debug['heuristic'] = [
                'picked_user_id' => $best->id,
                'best_score' => $bestScore,
            ];
        }
        return $best ?? $candidates->first();
    }

    private function normalizeTags($value): array
    {
        if (is_array($value)) {
            return array_map('strval', $value);
        }
        if (is_string($value)) {
            try {
                $decoded = json_decode($value, true);
                return is_array($decoded) ? array_map('strval', $decoded) : [];
            } catch (\Throwable) {
                return [];
            }
        }
        return [];
    }

    private function simpleCompatibilityScore(User $me, User $other, array $myInterests): int
    {
        $score = 0;
        $otherInterests = $this->normalizeTags($other->interests);
        $common = array_intersect($myInterests, $otherInterests);
        $score += count($common) * 10;
        if ($me->academic_program && $other->academic_program && $me->academic_program === $other->academic_program) {
            $score += 20;
        }
        return $score;
    }
}
