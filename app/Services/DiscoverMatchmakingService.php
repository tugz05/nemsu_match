<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Discover – random users (no scoring / no formula).
 *
 * Basis:
 * - Respects viewer's preferred_gender (if set; "No preference" behaves like null)
 *
 * Safety exclusions (minimal to maximize variety):
 * - self, blocked, blocked-by
 *
 * Note: Unlike Browse, we do NOT exclude following or swipes, so Discover can show fresh faces.
 */
class DiscoverMatchmakingService
{
    private const PER_PAGE = 20;

    public function getMatches(User $user, int $page = 1): LengthAwarePaginator
    {
        $excludedIds = $this->excludedUserIds($user);
        $page = max(1, $page);

        $baseQuery = $this->candidateQuery($user, $excludedIds);

        // Total is used only for UI meta; Discover returns a fresh random set every request.
        $total = (int) (clone $baseQuery)->count();

        $users = (clone $baseQuery)
            ->inRandomOrder()
            ->limit(self::PER_PAGE)
            ->get();

        /** @var array<int, array{user: User, compatibility_score: null, common_tags: array}> $items */
        $items = $users
            ->map(fn (User $u): array => [
                'user' => $u,
                'compatibility_score' => null,
                'common_tags' => [],
            ])
            ->values()
            ->all();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            self::PER_PAGE,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function excludedUserIds(User $user): array
    {
        // Discover is a random feed: only exclude safety-critical users (self, blocked).
        // We DON'T exclude following or swipes to maximize variety and ensure Discover ≠ Browse.
        $blocked = $user->blockedUsers()->pluck('blocked_id')->all();
        $blockedBy = $user->blockedBy()->pluck('blocker_id')->all();

        return array_values(array_unique(array_merge(
            [$user->id],
            $blocked,
            $blockedBy
        )));
    }

    private function candidateQuery(User $me, array $excludedIds): Builder
    {
        $q = User::query()
            ->where('profile_completed', true)
            ->whereNotIn('id', $excludedIds)
            ->select([
                'id', 'display_name', 'fullname', 'profile_picture',
                'campus', 'academic_program', 'year_level', 'date_of_birth',
                'courses', 'research_interests', 'extracurricular_activities',
                'academic_goals', 'interests', 'bio',
                'gender', 'relationship_status', 'looking_for',
                'preferred_gender',
                'preferred_age_min', 'preferred_age_max',
                'preferred_campuses', 'ideal_match_qualities', 'preferred_courses',
            ]);

        $preferredGender = $this->normalizedPreferredGender($me->preferred_gender);
        if ($preferredGender !== null) {
            $q->where('gender', $preferredGender);
        }

        return $q;
    }

    private function normalizedPreferredGender(mixed $value): ?string
    {
        $s = $this->normalizedString($value);
        if ($s === null) {
            return null;
        }
        if (strcasecmp($s, 'No preference') === 0) {
            return null;
        }
        return $s;
    }

    private function normalizedString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        return $s === '' ? null : $s;
    }
}

