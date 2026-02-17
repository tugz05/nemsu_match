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

        return response()->json([
            'match' => $matchData,
            'location_percentage' => $percentage,
            'in_proximity' => $inProximity,
            'match_proximity_percentage' => $matchProximityPct,
            'distance_to_match_m' => $distanceToMatchM !== null ? (int) round($distanceToMatchM) : null,
            'is_nearby_10m' => $isNearby10m,
            'campus' => $user->campus,
            'ai_debug' => $debug,
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
}
