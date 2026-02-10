<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Hybrid matching: content-based + weighted score.
 *
 * Match Score = α(academic) + β(interest) + γ(relationship) + δ(age) + ε(campus)
 * Each component is 0–100; weights sum to 1 so final score is 0–100.
 */
class MatchmakingService
{
    // Weights (α, β, γ, δ, ε) – sum = 1.0
    private const WEIGHT_ACADEMIC = 0.20;
    private const WEIGHT_INTEREST = 0.25;
    private const WEIGHT_RELATIONSHIP = 0.25;
    private const WEIGHT_AGE = 0.15;
    private const WEIGHT_CAMPUS = 0.15;

    private const CANDIDATE_LIMIT = 200;
    private const PER_PAGE = 20;
    /** Minimum compatibility score (0–100) to show on Discover/Browse. */
    private const MIN_COMPATIBILITY_SCORE = 35;

    public function getMatches(User $user, int $page = 1): LengthAwarePaginator
    {
        $excludedIds = $this->excludedUserIds($user);
        $candidates = $this->fetchCandidates($user, $excludedIds);

        $scored = $this->scoreAndSort($user, $candidates)
            ->filter(fn (array $item): bool => $item['compatibility_score'] >= self::MIN_COMPATIBILITY_SCORE)
            ->values();
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
     * Fetch profile_completed users with all fields needed for scoring.
     * Emphasizes looking-for by gender: if viewer has preferred_gender set, only candidates
     * of that gender are included (e.g. preferred_gender = Male → exclude non-male).
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
                'gender', 'relationship_status', 'looking_for',
                'preferred_age_min', 'preferred_age_max', 'preferred_campuses', 'ideal_match_qualities', 'preferred_courses',
            ])
            ->limit(self::CANDIDATE_LIMIT);

        // Looking-for by gender: only show candidates whose gender matches viewer's preferred_gender.
        // If preferred_gender is set (e.g. "Male"), exclude everyone who is not that gender.
        $preferredGender = $me->preferred_gender ? trim((string) $me->preferred_gender) : null;
        if ($preferredGender !== null && $preferredGender !== '') {
            $q->where('gender', $preferredGender);
        }

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
     * Score each candidate using weighted formula; sort by score descending.
     *
     * @return Collection<int, array{user: User, compatibility_score: int, common_tags: array, score_breakdown?: array}>
     */
    private function scoreAndSort(User $me, Collection $candidates): Collection
    {
        $myTags = $this->tagArray($me);
        $myInterestTags = $this->interestTagArray($me);

        $scored = $candidates->map(function (User $other) use ($me, $myTags, $myInterestTags): array {
            $academic = $this->academicCompatibility($me, $other);
            $interest = $this->interestCompatibility($me, $other, $myInterestTags);
            $relationship = $this->relationshipCompatibility($me, $other);
            $age = $this->ageCompatibility($me, $other);
            $campus = $this->campusCompatibility($me, $other);

            $weighted = (self::WEIGHT_ACADEMIC * $academic)
                + (self::WEIGHT_INTEREST * $interest)
                + (self::WEIGHT_RELATIONSHIP * $relationship)
                + (self::WEIGHT_AGE * $age)
                + (self::WEIGHT_CAMPUS * $campus);

            $score = (int) round(min(100, max(0, $weighted)));
            $otherTags = $this->tagArray($other);
            $commonTags = array_values(array_intersect($myTags, $otherTags));

            return [
                'user' => $other,
                'compatibility_score' => $score,
                'common_tags' => $commonTags,
                'score_breakdown' => [
                    'academic' => $academic,
                    'interest' => $interest,
                    'relationship' => $relationship,
                    'age' => $age,
                    'campus' => $campus,
                ],
            ];
        });

        return $scored->sortByDesc('compatibility_score')->values();
    }

    /**
     * Academic compatibility (0–100): program, year_level, courses overlap.
     */
    private function academicCompatibility(User $me, User $other): float
    {
        $score = 0.0;
        $max = 100.0;

        if ($me->academic_program && $other->academic_program) {
            $score += strcasecmp($me->academic_program, $other->academic_program) === 0 ? 40 : 0;
        }
        if ($me->year_level && $other->year_level) {
            $score += strcasecmp($me->year_level, $other->year_level) === 0 ? 30 : 0;
        }

        $myCourses = $this->normalizeArray($me->courses);
        $otherCourses = $this->normalizeArray($other->courses);
        if ($myCourses !== [] || $otherCourses !== []) {
            $jaccard = $this->jaccardSimilarity($myCourses, $otherCourses);
            $score += $jaccard * 15; // up to 15 pts from shared courses
        }

        // Preferred courses: bonus when the candidate has courses I prefer in a match
        $myPreferredCourses = $this->normalizeArray($me->preferred_courses);
        if ($myPreferredCourses !== [] && $otherCourses !== []) {
            $matchCount = count(array_intersect(
                array_map('strtolower', $myPreferredCourses),
                array_map('strtolower', $otherCourses)
            ));
            $preferredMatch = $matchCount / max(1, count($myPreferredCourses));
            $score += $preferredMatch * 15; // up to 15 pts from preferred-courses overlap
        }

        return min($max, $score);
    }

    /**
     * Interest compatibility (0–100): extracurricular_activities, interests, bio, ideal_match_qualities.
     */
    private function interestCompatibility(User $me, User $other, array $myInterestTags): float
    {
        $otherInterestTags = $this->interestTagArray($other);
        if ($myInterestTags === [] && $otherInterestTags === []) {
            return 50.0; // neutral when neither has interests
        }
        $jaccard = $this->jaccardSimilarity($myInterestTags, $otherInterestTags);
        $base = $jaccard * 70; // up to 70 from tag overlap

        // Bonus: other's qualities match what I look for (ideal_match_qualities)
        $myIdeal = $this->normalizeArray($me->ideal_match_qualities);
        $otherQualities = $this->qualitiesFromUser($other);
        $matchCount = count(array_intersect(
            array_map('strtolower', $myIdeal),
            array_map('strtolower', $otherQualities)
        ));
        $idealBonus = $myIdeal === [] ? 0 : min(30, $matchCount * 10);
        return min(100.0, $base + $idealBonus);
    }

    /**
     * Relationship compatibility (0–100): relationship_status, looking_for alignment.
     */
    private function relationshipCompatibility(User $me, User $other): float
    {
        $score = 50.0; // base neutral

        if ($me->looking_for && $other->looking_for) {
            if (strcasecmp($me->looking_for, $other->looking_for) === 0) {
                $score += 30;
            }
        }
        if ($me->relationship_status && $other->relationship_status) {
            $bothSingle = strcasecmp($me->relationship_status, 'Single') === 0
                && strcasecmp($other->relationship_status, 'Single') === 0;
            if ($bothSingle) {
                $score += 20;
            }
        }

        return min(100.0, $score);
    }

    /**
     * Age compatibility (0–100): each side's age falls in the other's preferred range (if set).
     */
    private function ageCompatibility(User $me, User $other): float
    {
        $myAge = $me->date_of_birth ? (int) $me->date_of_birth->diffInYears(now()) : null;
        $otherAge = $other->date_of_birth ? (int) $other->date_of_birth->diffInYears(now()) : null;

        $myMin = $me->preferred_age_min;
        $myMax = $me->preferred_age_max;
        $otherMin = $other->preferred_age_min;
        $otherMax = $other->preferred_age_max;

        $noPreference = ($myMin === null && $myMax === null) && ($otherMin === null && $otherMax === null);
        if ($noPreference) {
            return 100.0;
        }

        $score = 0.0;
        $checks = 0;
        $passed = 0;

        if ($otherMin !== null || $otherMax !== null) {
            $checks++;
            if ($myAge !== null && $this->ageInRange($myAge, $otherMin, $otherMax)) {
                $passed++;
            }
        }
        if ($myMin !== null || $myMax !== null) {
            $checks++;
            if ($otherAge !== null && $this->ageInRange($otherAge, $myMin, $myMax)) {
                $passed++;
            }
        }

        if ($checks === 0) {
            return 100.0;
        }
        return ($passed / $checks) * 100.0;
    }

    private function ageInRange(int $age, ?int $min, ?int $max): bool
    {
        if ($min !== null && $age < $min) {
            return false;
        }
        if ($max !== null && $age > $max) {
            return false;
        }
        return true;
    }

    /**
     * Campus preference (0–100): campus and preferred_campuses.
     */
    private function campusCompatibility(User $me, User $other): float
    {
        $myCampus = $me->campus ? trim($me->campus) : null;
        $otherCampus = $other->campus ? trim($other->campus) : null;
        $myPreferred = $this->normalizeArray($me->preferred_campuses);
        $otherPreferred = $this->normalizeArray($other->preferred_campuses);

        $score = 0.0;
        $max = 100.0;

        if ($myCampus && $otherCampus) {
            if (strcasecmp($myCampus, $otherCampus) === 0) {
                $score += 50;
            }
        }
        if ($otherCampus && $myPreferred !== []) {
            $inPreferred = in_array(strtolower($otherCampus), array_map('strtolower', $myPreferred), true);
            if ($inPreferred) {
                $score += 25;
            }
        }
        if ($myCampus && $otherPreferred !== []) {
            $inPreferred = in_array(strtolower($myCampus), array_map('strtolower', $otherPreferred), true);
            if ($inPreferred) {
                $score += 25;
            }
        }

        return min($max, $score);
    }

    private function tagArray(User $user): array
    {
        $courses = $this->normalizeArray($user->courses);
        $research = $this->normalizeArray($user->research_interests);
        $extra = $this->normalizeArray($user->extracurricular_activities);
        $goals = $this->normalizeArray($user->academic_goals);
        $interests = $this->normalizeArray($user->interests);
        return array_values(array_unique(array_merge($courses, $research, $extra, $goals, $interests)));
    }

    /** Tags used for interest/personality overlap: extracurricular, interests, bio words, ideal_match. */
    private function interestTagArray(User $user): array
    {
        $extra = $this->normalizeArray($user->extracurricular_activities);
        $interests = $this->normalizeArray($user->interests);
        $words = $user->bio ? array_filter(preg_split('/\s+/', trim($user->bio)), fn ($w) => strlen($w) > 2) : [];
        $words = array_map(fn ($w) => preg_replace('/[^a-zA-Z0-9]/', '', $w), $words);
        $words = array_filter($words);
        $qualities = $this->qualitiesFromUser($user);
        return array_values(array_unique(array_merge($extra, $interests, $words, $qualities)));
    }

    private function qualitiesFromUser(User $user): array
    {
        $ideal = $this->normalizeArray($user->ideal_match_qualities);
        $fromBio = $user->bio ? array_filter(preg_split('/\s+/', trim($user->bio)), fn ($w) => strlen($w) > 2) : [];
        return array_values(array_unique(array_merge($ideal, $fromBio)));
    }

    private function normalizeArray(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), fn ($v) => $v !== ''));
        }
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $this->normalizeArray($decoded) : [];
        }
        return [];
    }

    private function jaccardSimilarity(array $a, array $b): float
    {
        $a = array_map('strtolower', $a);
        $b = array_map('strtolower', $b);
        $a = array_values(array_unique($a));
        $b = array_values(array_unique($b));
        if ($a === [] && $b === []) {
            return 1.0;
        }
        $intersection = count(array_intersect($a, $b));
        $union = count(array_values(array_unique(array_merge($a, $b))));
        return $union > 0 ? $intersection / $union : 0.0;
    }
}
