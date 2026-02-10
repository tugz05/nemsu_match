<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * List notifications for the current user (paginated).
     * Returns full history: both read and unread, newest first (like other social platforms).
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 25), 50);

        // Exclude plain \"message\" notifications from the feed (chat has its own unread badge)
        $notifications = Notification::where('user_id', Auth::id())
            ->where('type', '!=', 'message')
            ->with('fromUser:id,display_name,fullname,profile_picture')
            ->latest()
            ->paginate($perPage);

        return response()->json($notifications);
    }

    /**
     * Unread count for the current user.
     */
    public function unreadCount()
    {
        // Exclude plain \"message\" notifications from unread count
        $count = Notification::where('user_id', Auth::id())
            ->where('type', '!=', 'message')
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(Notification $notification)
    {
        if ($notification->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json(['read' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Notification::where('user_id', Auth::id())->unread()->update(['read_at' => now()]);

        return response()->json(['read' => true]);
    }
}
