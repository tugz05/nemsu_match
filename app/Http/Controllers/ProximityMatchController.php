<?php

namespace App\Http\Controllers;

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
     */
    public function index(): Response
    {
        return Inertia::render('FindYourMatch');
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

        $likersWithin10m = $this->proximityMatch->getLikersWithin10mCount($user);

        return response()->json([
            'match' => $matchData,
            'location_percentage' => $percentage,
            'in_proximity' => $inProximity,
            'match_proximity_percentage' => $matchProximityPct,
            'distance_to_match_m' => $distanceToMatchM !== null ? (int) round($distanceToMatchM) : null,
            'is_nearby_10m' => $isNearby10m,
            'campus' => $user->campus,
            'ai_debug' => $debug,
            'likers_within_10m_count' => $likersWithin10m,
        ]);
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
