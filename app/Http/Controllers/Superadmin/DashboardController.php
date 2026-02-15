<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Post;
use App\Models\SwipeAction;
use App\Models\User;
use App\Models\UserMatch;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the superadmin dashboard
     */
    public function index(): Response
    {
        // Get statistics
        $stats = [
            'total_users' => User::count(),
            'active_users_today' => User::whereDate('last_seen_at', '>=', now()->subDay())->count(),
            'new_users_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            'verified_users' => User::whereNotNull('email_verified_at')->count(),
            'total_matches' => UserMatch::count(),
            'total_conversations' => Conversation::count(),
            'total_messages' => Message::count(),
            'total_swipes' => SwipeAction::count(),
            'total_posts' => Post::count(),
        ];

        // Recent users (last 10)
        $recentUsers = User::latest()
            ->limit(10)
            ->get(['id', 'display_name', 'fullname', 'email', 'profile_picture', 'created_at', 'last_seen_at']);

        // User growth data (last 30 days)
        $userGrowth = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $count = User::whereDate('created_at', $date)->count();
            $userGrowth[] = [
                'date' => $date,
                'count' => $count,
            ];
        }

        // Active users by gender
        $genderDistribution = [
            'male' => User::where('gender', 'male')->count(),
            'female' => User::where('gender', 'female')->count(),
            'other' => User::whereNotIn('gender', ['male', 'female'])->count(),
        ];

        return Inertia::render('Superadmin/Dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
            'userGrowth' => $userGrowth,
            'genderDistribution' => $genderDistribution,
        ]);
    }
}
