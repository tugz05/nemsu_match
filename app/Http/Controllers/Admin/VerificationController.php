<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Inertia\Inertia;

class VerificationController extends Controller
{
    /**
     * Display the list of users.
     */
    public function index()
    {
        // OLD (Broken):
        // $verifications = User::where('status', 'pending')->latest()->paginate(10);

        // NEW (Correct):
        // This fetches users who have NOT verified their email yet (where it is null)
        $verifications = User::whereNull('email_verified_at')
            ->latest()
            ->paginate(10);

        return Inertia::render('Admin/Verifications', [
            'verifications' => $verifications,
        ]);
    }

    /**
     * Approve a user (Mark email as verified).
     */
    public function approve(User $user)
    {
        // Mark the email as verified immediately
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        return back()->with('success', 'User approved successfully.');
    }

    /**
     * Reject a user (Delete the account).
     */
    public function reject(User $user)
    {
        // Permanently delete the user
        $user->delete();

        return back()->with('success', 'User rejected and removed.');
    }
}
