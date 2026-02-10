<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserMatch;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard
     */
    public function index(): Response
    {
        // Get basic statistics
        $stats = [
            'total_users' => User::count(),
            'active_users_today' => User::whereDate('last_seen_at', '>=', now()->subDay())->count(),
            'new_users_this_week' => User::where('created_at', '>=', now()->subWeek())->count(),
            'total_matches' => UserMatch::count(),
            'total_conversations' => Conversation::count(),
            'total_messages' => Message::count(),
            'total_posts' => Post::count(),
        ];

        // Recent users (last 5)
        $recentUsers = User::latest()
            ->limit(5)
            ->get(['id', 'display_name', 'fullname', 'email', 'profile_picture', 'created_at']);

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentUsers' => $recentUsers,
        ]);
    }
}
