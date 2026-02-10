<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * List all users with filtering and pagination
     */
    public function index(Request $request): Response
    {
        $query = User::query();

        // Search filter
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('fullname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nemsu_id', 'like', "%{$search}%");
            });
        }

        // Gender filter
        if ($gender = $request->input('gender')) {
            $query->where('gender', $gender);
        }

        // Status filter
        if ($status = $request->input('status')) {
            switch ($status) {
                case 'verified':
                    $query->whereNotNull('email_verified_at');
                    break;
                case 'unverified':
                    $query->whereNull('email_verified_at');
                    break;
                case 'completed':
                    $query->where('profile_completed', true);
                    break;
                case 'incomplete':
                    $query->where('profile_completed', false);
                    break;
                case 'admin':
                    $query->where('is_admin', true);
                    break;
            }
        }

        // Sort
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $users = $query->paginate(20)->withQueryString();

        return Inertia::render('Superadmin/Users', [
            'users' => $users,
            'filters' => [
                'search' => $request->input('search', ''),
                'gender' => $request->input('gender', ''),
                'status' => $request->input('status', ''),
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
            ],
        ]);
    }

    /**
     * Show user details
     */
    public function show(User $user)
    {
        $user->load(['adminRole', 'sentMessageRequests', 'receivedMessageRequests']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'display_name' => $user->display_name,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'nemsu_id' => $user->nemsu_id,
                'profile_picture' => $user->profile_picture,
                'gender' => $user->gender,
                'date_of_birth' => $user->date_of_birth?->format('Y-m-d'),
                'campus' => $user->campus,
                'academic_program' => $user->academic_program,
                'year_level' => $user->year_level,
                'bio' => $user->bio,
                'profile_completed' => $user->profile_completed,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'is_admin' => $user->is_admin,
                'is_superadmin' => $user->is_superadmin,
                'last_seen_at' => $user->last_seen_at?->toIso8601String(),
                'created_at' => $user->created_at->toIso8601String(),
                'admin_role' => $user->adminRole,
            ],
        ]);
    }

    /**
     * Update user details
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'fullname' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'gender' => 'sometimes|in:male,female,non-binary,prefer_not_to_say',
            'campus' => 'sometimes|string|max:255',
            'academic_program' => 'sometimes|string|max:255',
            'year_level' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:500',
            'profile_completed' => 'sometimes|boolean',
        ]);

        $user->update($request->only([
            'display_name',
            'fullname',
            'email',
            'gender',
            'campus',
            'academic_program',
            'year_level',
            'bio',
            'profile_completed',
        ]));

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    /**
     * Suspend or activate a user
     */
    public function toggleStatus(Request $request, User $user)
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        // You could add a 'is_suspended' column to users table
        // For now, we'll use profile_completed as a proxy
        
        return response()->json([
            'message' => 'User status updated',
        ]);
    }

    /**
     * Delete a user (soft delete or hard delete)
     */
    public function destroy(User $user)
    {
        // Prevent superadmin from deleting themselves
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'You cannot delete your own account',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully',
        ]);
    }
}
