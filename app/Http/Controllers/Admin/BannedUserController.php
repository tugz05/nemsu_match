<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BannedUserController extends Controller
{
    public function index()
    {
        $bannedUsers = User::whereNotNull('banned_at')
            ->latest('banned_at')
            ->paginate(10);

        return Inertia::render('Admin/BannedUsers', [
            'users' => $bannedUsers,
        ]);
    }

    public function store(Request $request, User $user)
    {
        $request->validate(['reason' => 'required|string']);

        $user->update([
            'banned_at' => now(),
            'ban_reason' => $request->reason,
        ]);

        return back()->with('success', 'User has been banned.');
    }

    public function destroy(User $user)
    {
        $user->update([
            'banned_at' => null,
            'ban_reason' => null,
        ]);

        return back()->with('success', 'User has been unbanned.');
    }
}
