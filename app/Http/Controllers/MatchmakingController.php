<?php

namespace App\Http\Controllers;

use App\Models\SwipeAction;
use App\Models\User;
use App\Services\MatchmakingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MatchmakingController extends Controller
{
    public function __construct(
        private MatchmakingService $matchmaking
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
                'courses' => $u->courses,
                'research_interests' => $u->research_interests,
                'extracurricular_activities' => $u->extracurricular_activities,
                'academic_goals' => $u->academic_goals,
                'interests' => $u->interests,
                'compatibility_score' => $item['compatibility_score'],
                'common_tags' => $item['common_tags'],
            ];
        })->values()->all();

        return response()->json([
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
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

            if ($targetLikedMe) {
                $matched = true;
                $other = User::find($targetId);
                if ($other) {
                    $otherUser = [
                        'id' => $other->id,
                        'display_name' => $other->display_name,
                        'fullname' => $other->fullname,
                        'profile_picture' => $other->profile_picture,
                    ];
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
}
