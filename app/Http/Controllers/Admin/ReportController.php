<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::with(['reporter', 'reportedUser'])
            ->latest()
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'type' => $report->type,
                    // Check mo sa database mo kung 'reason', 'description', o 'details' ang column name
                    // Dito ko nilagay pareho para sure na may makuha
                    'reason' => $report->reason ?? $report->description ?? 'No details provided',

                    'user' => $report->reportedUser
                        ? ($report->reportedUser->fullname ?? $report->reportedUser->name ?? 'Unknown')
                        : 'Unknown User',

                    'reporter' => $report->reporter
                        ? ($report->reporter->fullname ?? $report->reporter->name ?? 'Anonymous')
                        : 'Anonymous',

                    'date' => $report->created_at->format('M d, Y'),
                    'status' => $report->status,
                ];
            });

        return Inertia::render('Admin/Messagereport', [
            'reports' => $reports,
        ]);
    }

    public function destroy($id)
    {
        $report = Report::findOrFail($id);
        $report->delete();

        return back()->with('success', 'Report deleted successfully.');
    }

    /**
     * Update the status of the report.
     */
    public function update(Request $request, $id)
    {
        // 1. Validate kung tama ang status na pinasa
        $request->validate([
            'status' => 'required|in:Pending,Resolved,Dismissed',
        ]);

        // 2. Hanapin ang report
        $report = Report::findOrFail($id);

        // 3. Update ang status column
        $report->update([
            'status' => $request->status,
        ]);

        // 4. Balik sa page with success message
        return back()->with('success', 'Report status updated successfully.');
    }
}
