<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Optimized matchmaking: compatibility score from campus, program, year, and shared tags.
 * Single indexed query + in-memory scoring and pagination for performance.
 */
class MatchmakingService
{
    private const WEIGHT_CAMPUS = 25;
    private const WEIGHT_PROGRAM = 20;
    private const WEIGHT_YEAR = 10;
    private const WEIGHT_PER_SHARED_TAG = 3;
    private const MAX_TAG_POINTS = 30; // cap tag contribution
    private const CANDIDATE_LIMIT = 300;
    private const PER_PAGE = 20;

    public function getMatches(User $user, int $page = 1): LengthAwarePaginator
    {
        $excludedIds = $this->excludedUserIds($user);
        $candidates = $this->fetchCandidates($user, $excludedIds);

        $scored = $this->scoreAndSort($user, $candidates);
        $total = $scored->count();
        $lastPage = (int) ceil($total / self::PER_PAGE) ?: 1;
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * self::PER_PAGE;
        $items = $scored->slice($offset, self::PER_PAGE)->values()->all();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            self::PER_PAGE,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * IDs to exclude: self, following, blocked, blockers, already swiped.
     */
    private function excludedUserIds(User $user): array
    {
        $following = $user->following()->pluck('following_id')->all();
        $blocked = $user->blockedUsers()->pluck('blocked_id')->all();
        $blockedBy = $user->blockedBy()->pluck('blocker_id')->all();
        $swipedTargetIds = $user->swipeActions()->pluck('target_user_id')->all();

        return array_values(array_unique(array_merge(
            [$user->id],
            $following,
            $blocked,
            $blockedBy,
            $swipedTargetIds
        )));
    }

    /**
     * Single query: profile_completed users not in excluded list.
     * Prefer same campus then same program for DB-level ordering to reduce set size.
     */
    private function fetchCandidates(User $me, array $excludedIds): Collection
    {
        $q = User::query()
            ->where('profile_completed', true)
            ->whereNotIn('id', $excludedIds)
            ->select([
                'id', 'display_name', 'fullname', 'profile_picture',
                'campus', 'academic_program', 'year_level', 'date_of_birth',
                'courses', 'research_interests', 'extracurricular_activities',
                'academic_goals', 'interests', 'bio',
            ])
            ->limit(self::CANDIDATE_LIMIT);

        // Order by affinity to reduce irrelevant rows (optional; helps when limit is hit)
        $myCampus = $me->campus;
        $myProgram = $me->academic_program;
        if ($myCampus && $myProgram) {
            $q->orderByRaw("CASE WHEN campus = ? THEN 0 ELSE 1 END", [$myCampus])
                ->orderByRaw("CASE WHEN academic_program = ? THEN 0 ELSE 1 END", [$myProgram]);
        }
        $q->orderBy('id');

        return $q->get();
    }

    /**
     * Score each candidate and sort by score descending.
     *
     * @return Collection<int, array{user: User, compatibility_score: int, common_tags: array}>
     */
    private function scoreAndSort(User $me, Collection $candidates): Collection
    {
        $myTags = $this->tagArray($me);
        $myCampus = $me->campus;
        $myProgram = $me->academic_program;
        $myYear = $me->year_level;

        $scored = $candidates->map(function (User $other) use ($me, $myTags, $myCampus, $myProgram, $myYear): array {
            $score = 0;
            if ($myCampus && $other->campus === $myCampus) {
                $score += self::WEIGHT_CAMPUS;
            }
            if ($myProgram && $other->academic_program === $myProgram) {
                $score += self::WEIGHT_PROGRAM;
            }
            if ($myYear && $other->year_level === $myYear) {
                $score += self::WEIGHT_YEAR;
            }

            $otherTags = $this->tagArray($other);
            $shared = array_intersect($myTags, $otherTags);
            $tagPoints = min(count($shared) * self::WEIGHT_PER_SHARED_TAG, self::MAX_TAG_POINTS);
            $score += $tagPoints;

            return [
                'user' => $other,
                'compatibility_score' => min(100, $score),
                'common_tags' => array_values($shared),
            ];
        });

        return $scored->sortByDesc('compatibility_score')->values();
    }

    private function tagArray(User $user): array
    {
        $courses = is_array($user->courses) ? $user->courses : (array) json_decode($user->courses ?? '[]', true);
        $research = is_array($user->research_interests) ? $user->research_interests : (array) json_decode($user->research_interests ?? '[]', true);
        $extra = is_array($user->extracurricular_activities) ? $user->extracurricular_activities : (array) json_decode($user->extracurricular_activities ?? '[]', true);
        $goals = is_array($user->academic_goals) ? $user->academic_goals : (array) json_decode($user->academic_goals ?? '[]', true);
        $interests = is_array($user->interests) ? $user->interests : (array) json_decode($user->interests ?? '[]', true);

        return array_values(array_unique(array_merge(
            array_filter($courses, 'is_string'),
            array_filter($research, 'is_string'),
            array_filter($extra, 'is_string'),
            array_filter($goals, 'is_string'),
            array_filter($interests, 'is_string')
        )));
    }
}
