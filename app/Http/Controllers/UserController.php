<?php

namespace App\Http\Controllers;

use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Search users by display name or full name (for profile search like social apps).
     * Returns only profile_completed users, excludes current user. Max 20 results.
     */
    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));

        if ($q === '') {
            return response()->json(['data' => []]);
        }

        $currentUserId = Auth::id();
        $term = '%'.$q.'%';

        $users = User::query()
            ->where('profile_completed', true)
            ->where('id', '!=', $currentUserId)
            ->where(function ($query) use ($term): void {
                $query->where('display_name', 'like', $term)
                    ->orWhere('fullname', 'like', $term)
                    ->orWhere('name', 'like', $term);
            })
            ->select('id', 'display_name', 'fullname', 'profile_picture')
            ->limit(20)
            ->get();

        $followingIds = Auth::user()->following()->pluck('following_id')->flip();

        $data = $users->map(function (User $user) use ($followingIds): array {
            return [
                'id' => $user->id,
                'display_name' => $user->display_name,
                'fullname' => $user->fullname,
                'profile_picture' => $user->profile_picture,
                'is_following' => $followingIds->has($user->id),
            ];
        });

        return response()->json(['data' => $data->all()]);
    }

    /**
     * Toggle follow/unfollow a user. Returns current follow state.
     * Users cannot follow their own profile.
     */
    public function toggleFollow(User $user)
    {
        $currentUser = Auth::user();

        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'You cannot follow your own profile.'], 422);
        }

        if ($currentUser->isFollowing($user)) {
            $currentUser->unfollow($user);

            return response()->json(['following' => false]);
        }

        $currentUser->follow($user);

        $notification = Notification::notify($user->id, 'follow', $currentUser->id, 'user', $user->id);
        if ($notification) {
            broadcast(new NotificationSent($notification));
        }

        return response()->json(['following' => true]);
    }
}
