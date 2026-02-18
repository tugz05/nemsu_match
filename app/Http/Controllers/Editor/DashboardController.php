<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Announcement;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $stats = [
            'total_users'         => User::count(),
            'active_today'        => User::whereDate('updated_at', today())->count(),
            'total_announcements' => Announcement::count(),
            'total_posts'         => Post::count(),
        ];

        $recent_users = User::latest()
            ->take(5)
            ->get(['id', 'display_name', 'email', 'created_at', 'profile_picture']);

        $recent_announcements = Announcement::latest()
            ->take(5)
            ->get(['id', 'title', 'type', 'is_active', 'created_at']);

        return Inertia::render('Editor/Dashboard', [
            'stats'                => $stats,
            'recent_users'         => $recent_users,
            'recent_announcements' => $recent_announcements,
        ]);
    }
}