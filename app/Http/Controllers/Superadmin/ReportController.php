<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserReport;
use App\Models\UserReportAppeal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function appeals(Request $request): Response
    {
        $status = (string) $request->input('status', 'pending');
        $search = trim((string) $request->input('search', ''));

        $query = UserReportAppeal::query()
            ->with([
                'user:id,display_name,fullname,email,profile_picture,is_disabled,disabled_reason,disabled_at',
                'report:id,reported_user_id,reason,status',
            ]);

        if ($status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->whereHas('user', function ($q) use ($search): void {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('fullname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $appeals = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Superadmin/Appeals', [
            'appeals' => $appeals,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function reportedUsers(Request $request): Response
    {
        $status = (string) $request->input('status', 'pending');
        $search = trim((string) $request->input('search', ''));

        $query = UserReport::query()
            ->with([
                'reporter:id,display_name,fullname,email,profile_picture',
                'reportedUser:id,display_name,fullname,email,profile_picture,is_disabled,disabled_reason,disabled_at',
            ]);

        if ($status !== '') {
            $query->where('status', $status);
        }
        if ($search !== '') {
            $query->whereHas('reportedUser', function ($q) use ($search): void {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('fullname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $reports = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Superadmin/ReportedUsers', [
            'reports' => $reports,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
        ]);
    }

    public function disabledUsers(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));

        $query = User::query()
            ->where('is_disabled', true)
            ->withCount([
                'reportAppeals as pending_appeals_count' => fn ($q) => $q->where('status', 'pending'),
            ]);

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('display_name', 'like', "%{$search}%")
                    ->orWhere('fullname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('disabled_at')->paginate(20)->withQueryString();

        return Inertia::render('Superadmin/DisabledUsers', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    public function details(UserReport $report)
    {
        $report->load([
            'reporter:id,display_name,fullname,email,profile_picture',
            'reportedUser:id,display_name,fullname,email,profile_picture,is_disabled,disabled_reason,disabled_at',
        ]);

        $appeals = UserReportAppeal::query()
            ->where('user_report_id', $report->id)
            ->orWhere(function ($q) use ($report): void {
                $q->whereNull('user_report_id')
                    ->where('user_id', $report->reported_user_id);
            })
            ->latest()
            ->limit(10)
            ->get(['id', 'message', 'status', 'review_notes', 'created_at', 'reviewed_at']);

        return response()->json([
            'report' => [
                'id' => $report->id,
                'reason' => $report->reason,
                'description' => $report->description,
                'status' => $report->status,
                'created_at' => $report->created_at?->toIso8601String(),
                'reporter' => $report->reporter,
                'reported_user' => $report->reportedUser,
                'appeals' => $appeals,
            ],
        ]);
    }

    public function disableAccount(Request $request, UserReport $report)
    {
        $request->validate([
            'disabled_reason' => 'nullable|string|max:500',
        ]);

        $reportedUser = $report->reportedUser;
        if (! $reportedUser) {
            return response()->json(['message' => 'Reported user not found.'], 404);
        }

        $reason = trim((string) $request->input('disabled_reason'));
        if ($reason === '') {
            $reason = 'Account disabled due to policy violation after report review.';
        }

        $reportedUser->forceFill([
            'is_disabled' => true,
            'disabled_reason' => $reason,
            'disabled_at' => now(),
            'disabled_by' => Auth::id(),
        ])->save();

        $report->update(['status' => 'reviewed']);

        UserReport::query()
            ->where('reported_user_id', $reportedUser->id)
            ->where('status', 'pending')
            ->update(['status' => 'reviewed']);

        return response()->json(['message' => 'User account has been disabled successfully.']);
    }

    public function reviewAppeal(Request $request, UserReportAppeal $appeal)
    {
        $validated = $request->validate([
            'decision' => 'required|string|in:approved,rejected',
            'review_notes' => 'nullable|string|max:1200',
        ]);

        if ($appeal->status !== 'pending') {
            return response()->json(['message' => 'This appeal has already been reviewed.'], 422);
        }

        $appeal->forceFill([
            'status' => $validated['decision'],
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ])->save();

        $user = $appeal->user;
        if ($user && $validated['decision'] === 'approved') {
            $user->forceFill([
                'is_disabled' => false,
                'disabled_reason' => null,
                'disabled_at' => null,
                'disabled_by' => null,
            ])->save();
        }

        return response()->json([
            'message' => $validated['decision'] === 'approved'
                ? 'Appeal approved. Account re-enabled.'
                : 'Appeal rejected successfully.',
        ]);
    }
}

