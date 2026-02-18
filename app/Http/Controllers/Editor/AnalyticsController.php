<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index()
    {
        return Inertia::render('Editor/Analytics', [
            'stats'            => $this->getOverviewStats(),
            'daily_signups'    => $this->getDailySignups(),
            'match_stats'      => $this->getMatchStats(),
            'active_users'     => $this->getActiveUsersChart(),
        ]);
    }

    private function getOverviewStats(): array
    {
        $totalUsers      = User::count();
        $todaySignups    = User::whereDate('created_at', today())->count();
        $weekSignups     = User::whereBetween('created_at', [now()->startOfWeek(), now()])->count();
        $verifiedStudents = User::where('is_verified_student', true)->count();
        $suspendedUsers  = User::where('is_suspended', true)->count();

        // Match stats — adjust 'matches' to your actual table/relationship name
        $totalMatches    = DB::table('matches')->count();
        $todayMatches    = DB::table('matches')->whereDate('created_at', today())->count();
        $weekMatches     = DB::table('matches')
                            ->whereBetween('created_at', [now()->startOfWeek(), now()])
                            ->count();

        return compact(
            'totalUsers', 'todaySignups', 'weekSignups',
            'verifiedStudents', 'suspendedUsers',
            'totalMatches', 'todayMatches', 'weekMatches'
        );
    }

    private function getDailySignups(): array
    {
        return User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => $row->count])
            ->toArray();
    }

    private function getMatchStats(): array
    {
        // Adjust table name as needed
        return DB::table('matches')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => $row->count])
            ->toArray();
    }

    private function getActiveUsersChart(): array
    {
        // Users who updated their profile in the last 30 days (proxy for activity)
        return User::selectRaw('DATE(updated_at) as date, COUNT(*) as count')
            ->where('updated_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => $row->count])
            ->toArray();
    }
}