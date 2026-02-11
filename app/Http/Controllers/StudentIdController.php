<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class StudentIdController extends Controller
{
    /**
     * Show the NEMSU ID entry page (OTP-style layout).
     */
    public function show(): Response
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('home');
        }

        // Workspace-verified users or users who already have a student ID
        // should go straight to profile setup.
        if ($user->is_workspace_verified || $user->student_id) {
            if (! $user->profile_completed) {
                return redirect()->route('profile.setup');
            }

            return redirect()->route('browse');
        }

        return Inertia::render('auth/StudentId', [
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'display_name' => $user->display_name ?? $user->name,
            ],
        ]);
    }

    /**
     * Store the student ID for a personal Google account.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('home');
        }

        // Workspace-verified users should not hit this; just redirect.
        if ($user->is_workspace_verified) {
            return redirect()->route('browse');
        }

        $validated = $request->validate([
            'student_id' => [
                'required',
                'string',
                'regex:/^[0-9]{2}-[0-9]{5}$/', // Format 00-00000
                'unique:users,student_id,' . $user->id,
            ],
        ]);

        // Enforce enrollment year between 2018 and the current year.
        // First two digits are the last two digits of the year.
        $studentId = $validated['student_id'];
        $yearPart = (int) substr($studentId, 0, 2);
        $fullYear = 2000 + $yearPart; // e.g. "18" -> 2018
        $minYear = 2018;
        $maxYear = now()->year;

        if ($fullYear < $minYear || $fullYear > $maxYear) {
            return back()
                ->withErrors([
                    'student_id' => "Please enter a valid NEMSU ID: year must be between {$minYear} and {$maxYear}.",
                ])
                ->withInput();
        }

        $user->forceFill([
            'student_id' => $studentId,
        ])->save();

        if (! $user->profile_completed) {
            return redirect()->route('profile.setup')->with('success', 'Student ID saved. Please complete your profile to continue.');
        }

        return redirect()->route('browse')->with('success', 'Student ID saved successfully.');
    }
}

