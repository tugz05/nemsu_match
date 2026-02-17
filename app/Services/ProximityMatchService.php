<?php

namespace App\Services;

use App\Models\AiProximityMatch;
use App\Models\Campus;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class ProximityMatchService
{
    /** Distance (meters) at which user is considered "at" campus base (100% = proximity match). */
    public const PROXIMITY_RADIUS_M = 1;

    /** Max distance (meters) for percentage scale: beyond this = 0%. */
    public const MAX_DISTANCE_FOR_PERCENTAGE_M = 500;

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
