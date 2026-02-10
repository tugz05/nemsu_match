<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Superadmin\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    /**
     * List all admins and editors
     */
    public function index(): Response
    {
        $adminRoles = AdminRole::with(['user:id,display_name,fullname,email,profile_picture,created_at,last_seen_at', 'assignedBy:id,display_name,fullname'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($adminRole) {
                return [
                    'id' => $adminRole->id,
                    'user_id' => $adminRole->user_id,
                    'user' => $adminRole->user ? [
                        'id' => $adminRole->user->id,
                        'display_name' => $adminRole->user->display_name,
                        'fullname' => $adminRole->user->fullname,
                        'email' => $adminRole->user->email,
                        'profile_picture' => $adminRole->user->profile_picture,
                        'created_at' => $adminRole->user->created_at->toIso8601String(),
                        'last_seen_at' => $adminRole->user->last_seen_at?->toIso8601String(),
                        'is_online' => $adminRole->user->isOnline(),
                    ] : null,
                    'role' => $adminRole->role,
                    'permissions' => $adminRole->permissions,
                    'is_active' => $adminRole->is_active,
                    'assigned_at' => $adminRole->assigned_at->toIso8601String(),
                    'assigned_by' => $adminRole->assignedBy ? [
                        'id' => $adminRole->assignedBy->id,
                        'display_name' => $adminRole->assignedBy->display_name,
                        'fullname' => $adminRole->assignedBy->fullname,
                    ] : null,
                ];
            });

        return Inertia::render('Superadmin/Admins', [
            'adminRoles' => $adminRoles,
        ]);
    }

    /**
     * Assign admin/editor role to a user
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'role' => 'required|in:superadmin,admin,editor',
            'permissions' => 'nullable|array',
        ]);

        $user = User::findOrFail($request->user_id);

        // Check if user already has a role
        $existingRole = AdminRole::where('user_id', $user->id)->first();
        
        if ($existingRole) {
            return response()->json([
                'message' => 'User already has an admin role. Please update instead.',
            ], 422);
        }

        $adminRole = AdminRole::create([
            'user_id' => $user->id,
            'role' => $request->role,
            'permissions' => $request->permissions,
            'is_active' => true,
            'assigned_by' => Auth::id(),
        ]);

        // Update user is_admin flag
        $user->update(['is_admin' => true]);
        
        // Update user is_superadmin flag if role is superadmin
        if ($request->role === 'superadmin') {
            $user->update(['is_superadmin' => true]);
        }

        return response()->json([
            'message' => 'Admin role assigned successfully',
            'adminRole' => $adminRole->load('user:id,display_name,fullname,email,profile_picture'),
        ], 201);
    }

    /**
     * Update an admin role
     */
    public function update(Request $request, AdminRole $adminRole)
    {
        $request->validate([
            'role' => 'sometimes|in:superadmin,admin,editor',
            'permissions' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
        ]);

        $adminRole->update($request->only(['role', 'permissions', 'is_active']));

        // Update user flags
        $user = $adminRole->user;
        if ($request->has('role')) {
            $user->update(['is_superadmin' => $request->role === 'superadmin']);
        }
        
        if ($request->has('is_active') && !$request->is_active) {
            // If deactivating, remove admin flags
            $user->update(['is_admin' => false, 'is_superadmin' => false]);
        }

        return response()->json([
            'message' => 'Admin role updated successfully',
            'adminRole' => $adminRole->fresh()->load('user:id,display_name,fullname,email,profile_picture'),
        ]);
    }

    /**
     * Remove an admin role
     */
    public function destroy(AdminRole $adminRole)
    {
        // Prevent superadmin from removing themselves
        if ($adminRole->user_id === Auth::id() && $adminRole->role === 'superadmin') {
            return response()->json([
                'message' => 'You cannot remove your own superadmin role',
            ], 422);
        }

        $user = $adminRole->user;
        $adminRole->delete();

        // Update user flags
        $user->update([
            'is_admin' => false,
            'is_superadmin' => false,
        ]);

        return response()->json([
            'message' => 'Admin role removed successfully',
        ]);
    }

    /**
     * Search users for assigning admin roles
     */
    public function searchUsers(Request $request)
    {
        $query = $request->input('q', '');
        
        $users = User::where(function ($q) use ($query) {
                $q->where('display_name', 'like', "%{$query}%")
                    ->orWhere('fullname', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%");
            })
            ->whereDoesntHave('adminRole') // Exclude users who already have admin roles
            ->limit(20)
            ->get(['id', 'display_name', 'fullname', 'email', 'profile_picture']);

        return response()->json(['users' => $users]);
    }
}
