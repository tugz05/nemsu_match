<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Editor\EditorAuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, fn ($q, $s) =>
                $q->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%")
            )
            ->when($request->filter === 'suspended',  fn ($q) => $q->where('is_disabled', true))
            ->when($request->filter === 'banned',     fn ($q) => $q->whereNotNull('banned_at'))
            ->when($request->filter === 'verified',   fn ($q) => $q->where('is_workspace_verified', true))
            ->when($request->filter === 'unverified', fn ($q) => $q->where('is_workspace_verified', false))
            ->latest()
            ->paginate(20)
            ->through(fn ($u) => [
                'id'                  => $u->id,
                'name'                => $u->name,
                'email'               => $u->email,
                'avatar'              => $u->avatar ?? null,
                'is_suspended'        => $u->is_disabled ?? false,
                'is_banned'           => $u->banned_at !== null,
                'is_verified_student' => $u->is_workspace_verified ?? false,
                'suspension_reason'   => $u->disabled_reason ?? null,
                'suspended_at'        => $u->disabled_at?->toDateTimeString(),
                'created_at'          => $u->created_at->toDateTimeString(),
                'last_active'         => $u->last_seen_at?->toDateTimeString()
                                            ?? $u->updated_at->toDateTimeString(),
            ]);

        return Inertia::render('Editor/UserManagement', [
            'users'   => $users,
            'filters' => $request->only(['search', 'filter']),
        ]);
    }

    public function show(User $user)
    {
        return Inertia::render('Editor/UserProfile', [
            'user' => [
                'id'                  => $user->id,
                'name'                => $user->name,
                'email'               => $user->email,
                'avatar'              => $user->avatar ?? null,
                'is_suspended'        => $user->is_disabled ?? false,
                'is_banned'           => $user->banned_at !== null,
                'is_verified_student' => $user->is_workspace_verified ?? false,
                'suspension_reason'   => $user->disabled_reason ?? null,
                'suspended_at'        => $user->disabled_at?->toDateTimeString(),
                'created_at'          => $user->created_at->toDateTimeString(),
                // Match stats
                'total_matches'       => $user->swipeActionsReceived()->count() ?? 0,
                'matches_this_week'   => $user->swipeActionsReceived()
                                            ->whereBetween('created_at', [now()->startOfWeek(), now()])
                                            ->count() ?? 0,
                'total_posts'         => 0,
                'reports_against'     => $user->reportsAgainst()->count(),
            ],
        ]);
    }

    public function suspend(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->update([
            'is_disabled'     => true,
            'disabled_reason' => $request->reason,
            'disabled_at'     => now(),
            'disabled_by'     => auth()->id(),
        ]);

        EditorAuditLog::record('suspended', 'user', $user->id, [], ['reason' => $request->reason]);

        return back()->with('success', "User {$user->name} has been suspended.");
    }

    public function unsuspend(User $user)
    {
        $user->update([
            'is_disabled'     => false,
            'disabled_reason' => null,
            'disabled_at'     => null,
            'disabled_by'     => null,
        ]);

        EditorAuditLog::record('unsuspended', 'user', $user->id);

        return back()->with('success', "User {$user->name} has been unsuspended.");
    }

    public function ban(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $user->update([
            'banned_at'       => now(),
            'ban_reason'      => $request->reason,
            'is_disabled'     => true,
            'disabled_reason' => 'Permanently banned: ' . $request->reason,
            'disabled_at'     => now(),
            'disabled_by'     => auth()->id(),
        ]);

        EditorAuditLog::record('banned', 'user', $user->id, [], ['reason' => $request->reason]);

        return back()->with('success', "User {$user->name} has been permanently banned.");
    }

    public function verifyStudent(User $user)
    {
        $user->update(['is_workspace_verified' => true]);
        EditorAuditLog::record('verified_student', 'user', $user->id);

        return back()->with('success', "Student account verified for {$user->name}.");
    }

    public function unverifyStudent(User $user)
    {
        $user->update(['is_workspace_verified' => false]);
        EditorAuditLog::record('unverified_student', 'user', $user->id);

        return back()->with('success', "Student verification removed for {$user->name}.");
    }
}