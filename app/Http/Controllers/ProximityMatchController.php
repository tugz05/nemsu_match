<?php

namespace App\Http\Controllers;

use App\Events\NotificationSent;
use App\Models\Notification;
use App\Services\ProximityMatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ProximityMatchController extends Controller
{
    public function __construct(
        private ProximityMatchService $proximityMatch
    ) {}

    /**
     * Show the Find Your Match page.
     * Query: show_tap_back=1 when coming from "someone tapped your heart" notification — scrolls to tap-back section.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('FindYourMatch', [
            'show_tap_back' => $request->query('show_tap_back') === '1',
        ]);
    }

    /**
     * GET /api/proximity-match
     * Returns current AI match (same campus), location percentage, and whether in proximity (both within 1m of campus base).
     */
    public function data(Request $request)
    {
        $user = Auth::user();
        $debug = [];
        $match = $this->proximityMatch->getOrAssignMatch($user, $debug);
        $percentage = $this->proximityMatch->locationPercentage($user);
        $inProximity = false;
        $matchData = null;
        $distanceToMatchM = null;
        $matchProximityPct = null;
        $isNearby10m = false;

        if ($match) {
            $inProximity = $this->proximityMatch->isProximityMatch($user, $match);
            $distanceToMatchM = $this->proximityMatch->distanceToMatchMeters($user, $match);
            $matchProximityPct = $this->proximityMatch->matchProximityPercentage($user, $match);
            // 10m radius for \"nearby\" state
            $isNearby10m = $this->proximityMatch->isNearbyMatch($user, $match, 10);
            $matchData = [
                'id' => $match->id,
                'display_name' => $match->display_name,
                'profile_picture' => $match->profile_picture,
            ];
        }

        $nearbyCount = $this->proximityMatch->getLikersWithin10mCount($user);
        $tappedYouCount = $this->proximityMatch->getTappedYouCount($user);
        $nearbyHearts = $this->proximityMatch->getNearbyLikersWithTokens($user);
        $tappersForTapBack = $this->proximityMatch->getTappersForTapBack($user);

        $payload = [
            'match' => $matchData,
            'location_percentage' => $percentage,
            'in_proximity' => $inProximity,
            'match_proximity_percentage' => $matchProximityPct,
            'distance_to_match_m' => $distanceToMatchM !== null ? (int) round($distanceToMatchM) : null,
            'is_nearby_10m' => $isNearby10m,
            'campus' => $user->campus,
            'preferred_gender' => $user->preferred_gender,
            'ai_debug' => $debug,
            'likers_within_10m_count' => $nearbyCount,
            'tapped_you_count' => $tappedYouCount,
            'tappers_for_tap_back' => $tappersForTapBack,
            'nearby_hearts' => $nearbyHearts,
        ];

        if ($request->query('debug') === '1') {
            $payload['proximity_debug'] = $this->proximityMatch->getProximityDebugInfo($user);
        }

        return response()->json($payload);
    }

    /**
     * POST /api/proximity-match/notify-nearby
     * When the viewer taps a heart, record the tap and create an anonymous notification.
     * The notification appears in the Notification module but is shown anonymously (no profile/name).
     */
    public function notifyNearby(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        $user = Auth::user();
        $likerId = $this->proximityMatch->decodeNearbyTapToken($request->input('token'), $user);
        if ($likerId === null) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }
        $this->proximityMatch->recordTap($user, $likerId);

        $notification = Notification::notify(
            $likerId,
            'nearby_heart_tap',
            $user->id,
            'user',
            $user->id,
            []
        );
        if ($notification) {
            broadcast(new NotificationSent($notification));
        }

        return response()->json(['message' => 'Notification sent.']);
    }

    /**
     * POST /api/proximity-match/tap-back
     * Tap back someone who tapped you. If mutual, creates anonymous chat room and returns room_id.
     */
    public function tapBack(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        $user = Auth::user();
        $tapperId = $this->proximityMatch->decodeTapBackToken($request->input('token'), $user);
        if ($tapperId === null) {
            return response()->json(['message' => 'Invalid or expired token.'], 422);
        }
        $result = $this->proximityMatch->tapBack($user, $tapperId);
        return response()->json($result);
    }

    /**
     * POST /api/proximity-match/reset
     * Reset current match so user gets a new one on next load.
     */
    public function reset(Request $request)
    {
        $user = Auth::user();
        $this->proximityMatch->resetMatch($user);
        return response()->json(['ok' => true]);
    }

    /**
     * GET /api/proximity-match/radar
     * Returns radar data: campus base lat/long, current user position relative to base,
     * and nearest same-campus users within radius (distance and bearing from base, distance from me).
     * Used for the radar UI with heart blips around the campus base point.
     */
    public function radar(Request $request)
    {
        $user = Auth::user();
        return response()->json($this->proximityMatch->getRadarData($user));
    }
}
