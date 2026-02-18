<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Editor\ContentReport;
use App\Models\Editor\EditorAuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContentReportsController extends Controller
{
    public function index(Request $request)
    {
        $reports = ContentReport::with(['reporter:id,name,email'])
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->type,   fn ($q, $t) => $q->where('reportable_type', $t))
            ->latest()
            ->paginate(20)
            ->through(fn ($r) => [
                'id'              => $r->id,
                'reporter_name'   => $r->reporter?->name ?? 'Deleted User',
                'reporter_email'  => $r->reporter?->email ?? '—',
                'reportable_type' => class_basename($r->reportable_type),
                'reportable_id'   => $r->reportable_id,
                'reason'          => $r->reason,
                'reason_label'    => ContentReport::reasons()[$r->reason] ?? $r->reason,
                'description'     => $r->description,
                'status'          => $r->status,
                'review_notes'    => $r->review_notes,
                'created_at'      => $r->created_at->toDateTimeString(),
                'reviewed_at'     => $r->reviewed_at?->toDateTimeString(),
            ]);

        return Inertia::render('Editor/ContentReports', [
            'reports' => $reports,
            'filters' => $request->only(['status', 'type']),
            'reasons' => ContentReport::reasons(),
            'stats'   => [
                'pending'  => ContentReport::where('status', 'pending')->count(),
                'reviewed' => ContentReport::where('status', 'reviewed')->count(),
                'resolved' => ContentReport::where('status', 'resolved')->count(),
            ],
        ]);
    }

    public function review(Request $request, ContentReport $report)
    {
        $request->validate([
            'status'       => 'required|in:reviewed,resolved,dismissed',
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $report->update([
            'status'       => $request->status,
            'review_notes' => $request->review_notes,
            'reviewed_by'  => auth()->id(),
            'reviewed_at'  => now(),
        ]);

        EditorAuditLog::record('reviewed_report', 'content_report', $report->id, [], [
            'status' => $request->status,
            'notes'  => $request->review_notes,
        ]);

        return back()->with('success', 'Report has been reviewed.');
    }
}