<?php

namespace App\Http\Controllers;

use App\Models\UserReport;
use App\Models\UserReportAppeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DisabledAccountController extends Controller
{
    public function show(): Response|\Illuminate\Http\RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        if (! $user->is_disabled) {
            return redirect()->route('browse');
        }

        $latestAppeal = UserReportAppeal::query()
            ->where('user_id', $user->id)
            ->latest()
            ->first();

        return Inertia::render('DisabledAccount', [
            'user' => [
                'id' => $user->id,
                'display_name' => $user->display_name,
                'email' => $user->email,
                'is_disabled' => (bool) $user->is_disabled,
                'disabled_reason' => $user->disabled_reason,
                'disabled_at' => $user->disabled_at?->toIso8601String(),
            ],
            'latestAppeal' => $latestAppeal ? [
                'id' => $latestAppeal->id,
                'message' => $latestAppeal->message,
                'status' => $latestAppeal->status,
                'review_notes' => $latestAppeal->review_notes,
                'created_at' => $latestAppeal->created_at?->toIso8601String(),
            ] : null,
        ]);
    }

    public function submitAppeal(Request $request)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string|min:20|max:1200',
        ]);

        if (! $user->is_disabled) {
            return response()->json(['message' => 'Your account is active. Appeal is not required.'], 422);
        }

        $hasPendingAppeal = UserReportAppeal::query()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();
        if ($hasPendingAppeal) {
            return response()->json(['message' => 'You already have a pending appeal. Please wait for review.'], 409);
        }

        $latestReport = UserReport::query()
            ->where('reported_user_id', $user->id)
            ->latest()
            ->first();

        UserReportAppeal::create([
            'user_id' => $user->id,
            'user_report_id' => $latestReport?->id,
            'message' => trim((string) $request->input('message')),
            'status' => 'pending',
        ]);

        return response()->json(['message' => 'Appeal submitted successfully. Superadmin will review your request.']);
    }
}

