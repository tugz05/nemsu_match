<?php

namespace App\Http\Controllers;

use App\Events\NotificationSent;
use App\Models\Conversation;
use App\Models\UserMatch;
use App\Models\Notification;
use App\Models\SwipeAction;
use App\Models\User;
use App\Services\MatchmakingService;
use App\Services\DiscoverMatchmakingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class MatchmakingController extends Controller
{
    public function __construct(
        private MatchmakingService $matchmaking,
        private DiscoverMatchmakingService $discoverMatchmaking
    ) {}

    /**
     * GET /api/matchmaking?page=1
     * Returns paginated match suggestions with compatibility score for the current user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        if (! $user->profile_completed) {
            return response()->json(['message' => 'Complete your profile to see matches.'], 422);
        }

        $page = max(1, (int) $request->input('page', 1));
        $paginator = $this->matchmaking->getMatches($user, $page);

        $data = collect($paginator->items())->map(function (array $item): array {
            $u = $item['user'];
            $age = $u->date_of_birth
                ? (int) $u->date_of_birth->diffInYears(now())
                : null;

            $out = [
                'id' => $u->id,
                'display_name' => $u->display_name,
                'fullname' => $u->fullname,
                'profile_picture' => $u->profile_picture,
                'campus' => $u->campus,
                'academic_program' => $u->academic_program,
                'year_level' => $u->year_level,
                'bio' => $u->bio,
                'date_of_birth' => $u->date_of_birth?->format('Y-m-d'),
                'age' => $age,
                'gender' => $u->gender,
                'courses' => $u->courses,
                'research_interests' => $u->research_interests,
                'extracurricular_activities' => $u->extracurricular_activities,
                'academic_goals' => $u->academic_goals,
                'interests' => $u->interests,
                'compatibility_score' => $item['compatibility_score'],
                'common_tags' => $item['common_tags'],
            ];
            if (isset($item['score_breakdown'])) {
                $out['score_breakdown'] = $item['score_breakdown'];
            }
            return $out;
        })->values()->all();

        // Notify users who appear with 70%+ compatibility (they see "X has you as a 70%+ match")
        $this->notifyHighCompatibilityMatches($user, $paginator->items());

        return response()->json([
            'feed' => 'browse_scored',
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * GET /api/matchmaking/discover?page=1
     * Discover suggestions (random feed).
     */
    public function discover(Request $request)
    {
        $user = Auth::user();

        if (! $user->profile_completed) {
            return response()->json(['message' => 'Complete your profile to see matches.'], 422);
        }

        $page = max(1, (int) $request->input('page', 1));
        $paginator = $this->discoverMatchmaking->getMatches($user, $page);

        $data = collect($paginator->items())->map(function (array $item): array {
            $u = $item['user'];
            $age = $u->date_of_birth
                ? (int) Carbon::instance($u->date_of_birth)->diffInYears(now())
                : null;

            $out = [
                'id' => $u->id,
                'display_name' => $u->display_name,
                'fullname' => $u->fullname,
                'profile_picture' => $u->profile_picture,
                'campus' => $u->campus,
                'academic_program' => $u->academic_program,
                'year_level' => $u->year_level,
                'bio' => $u->bio,
                'date_of_birth' => $u->date_of_birth?->format('Y-m-d'),
                'age' => $age,
                'gender' => $u->gender,
                'courses' => $u->courses,
                'research_interests' => $u->research_interests,
                'extracurricular_activities' => $u->extracurricular_activities,
                'academic_goals' => $u->academic_goals,
                'interests' => $u->interests,
                'compatibility_score' => $item['compatibility_score'],
                'common_tags' => $item['common_tags'],
            ];
            if (isset($item['score_breakdown'])) {
                $out['score_breakdown'] = $item['score_breakdown'];
            }
            return $out;
        })->values()->all();

        return response()->json([
            'feed' => 'discover_random',
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * For each candidate with compatibility_score >= 70%, send them a one-time notification
     * that the current user has them as a high match. Dedupe: at most one per (viewer, candidate) per 24h.
     *
     * @param array<int, array{user: User, compatibility_score: int, ...}> $items
     */
    private function notifyHighCompatibilityMatches(User $viewer, array $items): void
    {
        $threshold = 70;
        $cutoff = now()->subDay();

        foreach ($items as $item) {
            $score = $item['compatibility_score'] ?? 0;
            if ($score < $threshold) {
                continue;
            }
            $candidate = $item['user'];
            if (! $candidate instanceof User || $candidate->id === $viewer->id) {
                continue;
            }
            $alreadyNotified = Notification::where('user_id', $candidate->id)
                ->where('from_user_id', $viewer->id)
                ->where('type', 'high_compatibility_match')
                ->where('created_at', '>=', $cutoff)
                ->exists();
            if ($alreadyNotified) {
                continue;
            }
            $notification = Notification::notify(
                recipientUserId: $candidate->id,
                type: 'high_compatibility_match',
                fromUserId: $viewer->id,
                notifiableType: 'user',
                notifiableId: $viewer->id,
                data: ['compatibility_score' => $score]
            );
            if ($notification) {
                broadcast(new NotificationSent($notification));
            }
        }
    }

    /**
     * POST /api/matchmaking/action
     * Record swipe action (dating, friend, study_buddy, ignored). Follows on like. Returns matched: true if mutual like.
     */
    public function action(Request $request)
    {
        $request->validate([
            'target_user_id' => 'required|integer|exists:users,id',
            'intent' => 'required|string|in:dating,friend,study_buddy,ignored',
        ]);

        $me = Auth::user();
        $targetId = (int) $request->input('target_user_id');
        $intent = $request->input('intent');

        if ($me->id === $targetId) {
            return response()->json(['message' => 'Invalid target.'], 422);
        }

        SwipeAction::updateOrCreate(
            ['user_id' => $me->id, 'target_user_id' => $targetId],
            ['intent' => $intent]
        );

        $matched = false;
        $otherUser = null;

        if (SwipeAction::isLikeIntent($intent)) {
            $targetLikedMe = SwipeAction::where('user_id', $targetId)
                ->where('target_user_id', $me->id)
                ->whereIn('intent', [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY])
                ->exists();

            // Only notify "X sent you a heart match" when it's not already a mutual match (matchback case: we'll send "It's a match!" instead)
            if (! $targetLikedMe) {
                $type = match ($intent) {
                    SwipeAction::INTENT_DATING => 'match_dating',
                    SwipeAction::INTENT_FRIEND => 'match_friend',
                    SwipeAction::INTENT_STUDY_BUDDY => 'match_study_buddy',
                    default => null,
                };
                if ($type !== null) {
                    $notification = Notification::notify(
                        recipientUserId: $targetId,
                        type: $type,
                        fromUserId: $me->id,
                        notifiableType: 'user',
                        notifiableId: $me->id,
                        data: ['intent' => $intent]
                    );
                    if ($notification) {
                        broadcast(new NotificationSent($notification));
                    }
                }
            }

            if ($targetLikedMe) {
                $matched = true;
                $other = User::find($targetId);
                if ($other) {
                    UserMatch::record($me->id, $other->id, $intent);
                    Conversation::between($me->id, $other->id);
                    $otherUser = [
                        'id' => $other->id,
                        'display_name' => $other->display_name,
                        'fullname' => $other->fullname,
                        'profile_picture' => $other->profile_picture,
                    ];
                    // Notify the other user that they have a new match (so they see "You and [Name] matched!")
                    $mutualNotif = Notification::notify(
                        recipientUserId: $targetId,
                        type: 'mutual_match',
                        fromUserId: $me->id,
                        notifiableType: 'user',
                        notifiableId: $me->id,
                        data: ['intent' => $intent]
                    );
                    if ($mutualNotif) {
                        broadcast(new NotificationSent($mutualNotif));
                    }
                }
            }
        }

        return response()->json([
            'matched' => $matched,
            'other_user' => $otherUser,
        ]);
    }

    /**
     * GET /api/matchmaking/likes?intent=dating|friend|study_buddy&page=1
     * Returns paginated list of users the current user has liked with the given intent.
     */
    public function likes(Request $request)
    {
        $request->validate([
            'intent' => 'required|string|in:dating,friend,study_buddy',
            'page' => 'sometimes|integer|min:1',
        ]);

        $user = Auth::user();
        $intent = $request->input('intent');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $query = SwipeAction::query()
            ->where('user_id', $user->id)
            ->where('intent', $intent)
            ->with('targetUser:id,display_name,fullname,profile_picture,campus,academic_program,year_level,bio,date_of_birth,gender');

        $paginator = $query->orderByDesc('updated_at')->paginate($perPage, ['*'], 'page', $page);

        $targetIds = collect($paginator->items())
            ->filter(fn (SwipeAction $action) => $action->targetUser !== null)
            ->map(fn (SwipeAction $action) => $action->target_user_id)
            ->values()
            ->all();

        $matchedTargetIds = [];
        if ($targetIds !== []) {
            $matchedTargetIds = SwipeAction::query()
                ->whereIn('user_id', $targetIds)
                ->where('target_user_id', $user->id)
                ->whereIn('intent', [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY])
                ->pluck('user_id')
                ->all();
        }

        $data = collect($paginator->items())
            ->filter(fn (SwipeAction $action) => $action->targetUser !== null)
            ->map(function (SwipeAction $action) use ($matchedTargetIds): array {
                $u = $action->targetUser;
                $age = $u->date_of_birth
                    ? (int) $u->date_of_birth->diffInYears(now())
                    : null;
                $matched = in_array($u->id, $matchedTargetIds, true);

                return [
                    'id' => $u->id,
                    'display_name' => $u->display_name,
                    'fullname' => $u->fullname,
                    'profile_picture' => $u->profile_picture,
                    'campus' => $u->campus,
                    'academic_program' => $u->academic_program,
                    'year_level' => $u->year_level,
                    'bio' => $u->bio,
                    'date_of_birth' => $u->date_of_birth?->format('Y-m-d'),
                    'age' => $age,
                    'gender' => $u->gender,
                    'liked_at' => $action->updated_at->toIso8601String(),
                    'matched' => $matched,
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * GET /api/matchmaking/who-liked-me?page=1&intent=dating|friend|study_buddy
     * Users who liked you (heart/smile/study buddy) and you haven't liked back yet — for match-back.
     */
    public function whoLikedMe(Request $request)
    {
        $me = Auth::user();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $intentFilter = $request->input('intent');

        $intents = [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY];
        if (in_array($intentFilter, $intents, true)) {
            $intents = [$intentFilter];
        }

        $query = SwipeAction::query()
            ->where('target_user_id', $me->id)
            ->whereIn('intent', $intents)
            ->with('user:id,display_name,fullname,profile_picture,campus,academic_program,year_level,bio,date_of_birth,gender');

        $paginator = $query->orderByDesc('updated_at')->paginate($perPage, ['*'], 'page', $page);

        $userIds = collect($paginator->items())->map(fn (SwipeAction $a) => $a->user_id)->unique()->values()->all();
        $myLikeBack = [];
        if ($userIds !== []) {
            $myLikeBack = SwipeAction::query()
                ->where('user_id', $me->id)
                ->whereIn('target_user_id', $userIds)
                ->whereIn('intent', [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY])
                ->pluck('target_user_id')
                ->all();
        }

        $data = collect($paginator->items())
            ->filter(fn (SwipeAction $a) => $a->user !== null && ! in_array($a->user_id, $myLikeBack, true))
            ->map(function (SwipeAction $action): array {
                $u = $action->user;
                $age = $u->date_of_birth ? (int) $u->date_of_birth->diffInYears(now()) : null;
                return [
                    'id' => $u->id,
                    'display_name' => $u->display_name,
                    'fullname' => $u->fullname,
                    'profile_picture' => $u->profile_picture,
                    'campus' => $u->campus,
                    'academic_program' => $u->academic_program,
                    'year_level' => $u->year_level,
                    'bio' => $u->bio,
                    'date_of_birth' => $u->date_of_birth?->format('Y-m-d'),
                    'age' => $age,
                    'gender' => $u->gender,
                    'their_intent' => $action->intent,
                    'liked_at' => $action->updated_at->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * GET /api/matchmaking/who-liked-me-count
     * Count of users who liked you (match-back) and you haven't liked back yet. Used for Discover badge.
     */
    public function whoLikedMeCount()
    {
        $me = Auth::user();
        $userIdsWhoLikedMe = SwipeAction::query()
            ->where('target_user_id', $me->id)
            ->whereIn('intent', [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY])
            ->distinct()
            ->pluck('user_id')
            ->all();
        if ($userIdsWhoLikedMe === []) {
            return response()->json(['count' => 0]);
        }
        $likedBackCount = SwipeAction::query()
            ->where('user_id', $me->id)
            ->whereIn('target_user_id', $userIdsWhoLikedMe)
            ->whereIn('intent', [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY])
            ->distinct()
            ->pluck('target_user_id')
            ->count();
        $count = count($userIdsWhoLikedMe) - $likedBackCount;
        return response()->json(['count' => max(0, $count)]);
    }

    /**
     * GET /api/matchmaking/my-recent-likes
     * Returns paginated list of users YOU have liked (with heart, smile, or study buddy).
     * Shows all three intent types together.
     */
    public function myRecentLikes(Request $request)
    {
        $me = Auth::user();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $intents = [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY];

        $query = SwipeAction::query()
            ->where('user_id', $me->id)
            ->whereIn('intent', $intents)
            ->with('targetUser:id,display_name,fullname,profile_picture,campus,academic_program,year_level,bio,date_of_birth,gender');

        $paginator = $query->orderByDesc('updated_at')->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())
            ->filter(fn (SwipeAction $a) => $a->targetUser !== null)
            ->map(function (SwipeAction $action): array {
                $u = $action->targetUser;
                $age = $u->date_of_birth ? (int) $u->date_of_birth->diffInYears(now()) : null;
                return [
                    'id' => $u->id,
                    'display_name' => $u->display_name,
                    'fullname' => $u->fullname,
                    'profile_picture' => $u->profile_picture,
                    'campus' => $u->campus,
                    'academic_program' => $u->academic_program,
                    'year_level' => $u->year_level,
                    'bio' => $u->bio,
                    'date_of_birth' => $u->date_of_birth?->format('Y-m-d'),
                    'age' => $age,
                    'gender' => $u->gender,
                    'my_intent' => $action->intent,
                    'liked_at' => $action->updated_at->toIso8601String(),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * GET /api/matchmaking/mutual?page=1&intent=dating|friend|study_buddy
     * Mutual matches: users you and who have both liked each other. Optional intent filters by your like type.
     */
    public function mutualMatches(Request $request)
    {
        $me = Auth::user();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;
        $intentFilter = $request->input('intent');

        $theyLikedMe = SwipeAction::query()
            ->where('target_user_id', $me->id)
            ->whereIn('intent', [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY])
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        if ($theyLikedMe === []) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
            ]);
        }

        $intents = [SwipeAction::INTENT_DATING, SwipeAction::INTENT_FRIEND, SwipeAction::INTENT_STUDY_BUDDY];
        if (in_array($intentFilter, $intents, true)) {
            $intents = [$intentFilter];
        }

        $myLikes = SwipeAction::query()
            ->where('user_id', $me->id)
            ->whereIn('target_user_id', $theyLikedMe)
            ->whereIn('intent', $intents)
            ->get()
            ->keyBy('target_user_id');

        $mutualIds = collect($theyLikedMe)->filter(fn ($id) => $myLikes->has($id))->values()->all();
        if ($mutualIds === []) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $perPage,
                'total' => 0,
            ]);
        }

        $users = User::query()
            ->whereIn('id', $mutualIds)
            ->get()
            ->keyBy('id');

        $withOrder = collect($mutualIds)->map(fn ($id) => [
            'id' => $id,
            'matched_at' => $myLikes->get($id)?->updated_at?->toIso8601String() ?? '',
            'intent' => $myLikes->get($id)?->intent ?? SwipeAction::INTENT_DATING,
        ])->values();
        $sorted = $withOrder->sortByDesc('matched_at')->values();
        $total = $sorted->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $pageItems = $sorted->forPage($page, $perPage);

        $data = $pageItems->map(function (array $item) use ($users): ?array {
            $u = $users->get($item['id']);
            if (! $u) {
                return null;
            }
            $age = $u->date_of_birth
                ? (int) Carbon::instance($u->date_of_birth)->diffInYears(now())
                : null;
            return [
                'id' => $u->id,
                'display_name' => $u->display_name,
                'fullname' => $u->fullname,
                'profile_picture' => $u->profile_picture,
                'campus' => $u->campus,
                'academic_program' => $u->academic_program,
                'year_level' => $u->year_level,
                'bio' => $u->bio,
                'date_of_birth' => $u->date_of_birth?->format('Y-m-d'),
                'age' => $age,
                'gender' => $u->gender,
                'matched_at' => $item['matched_at'],
                'intent' => $item['intent'],
            ];
        })->filter()->values()->all();

        return response()->json([
            'data' => $data,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }
}
