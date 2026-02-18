<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\EditorAuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    public function index(): Response
    {
        $announcements = Announcement::with('creator:id,name')
            ->latest()
            ->paginate(15)
            ->through(fn ($a) => [
                'id'                 => $a->id,
                'title'              => $a->title,
                'content'            => $a->content,
                'type'               => $a->type,
                'is_active'          => $a->is_active,
                'target_group'       => $a->target_group,
                'target_group_label' => $a->target_group_label,
                'scheduled_at'       => $a->scheduled_at?->toDateTimeString(),
                'status'             => $a->status,
                'created_by_name'    => $a->creator?->name ?? 'Unknown',
                'created_at'         => $a->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Editor/Announcements', [
            'announcements' => $announcements,
            'target_groups' => [
                ['value' => 'all',       'label' => 'All Users'],
                ['value' => 'male',      'label' => 'Male Users'],
                ['value' => 'female',    'label' => 'Female Users'],
                ['value' => 'verified',  'label' => 'Verified Students'],
                ['value' => 'new_users', 'label' => 'New Users (< 7 days)'],
            ],
            'types' => [
                ['value' => 'general', 'label' => 'General'],
                ['value' => 'urgent',  'label' => 'Urgent'],
                ['value' => 'event',   'label' => 'Event'],
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'type'         => 'nullable|string|in:general,urgent,event',
            'is_active'    => 'boolean',
            'target_group' => 'required|in:all,male,female,verified,new_users',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $announcement = Announcement::create([
            'title'        => $request->title,
            'content'      => $request->content,
            'type'         => $request->type ?? 'general',
            'user_id'      => auth()->id(),
            'is_active'    => $request->boolean('is_active', true),
            'target_group' => $request->target_group ?? 'all',
            'scheduled_at' => $request->scheduled_at,
        ]);

        EditorAuditLog::record('created', 'announcement', $announcement->id, [], $request->only(['title', 'type', 'target_group']));

        return back()->with('success', 'Announcement published!');
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'content'      => 'required|string',
            'type'         => 'nullable|string|in:general,urgent,event',
            'is_active'    => 'boolean',
            'target_group' => 'required|in:all,male,female,verified,new_users',
            'scheduled_at' => 'nullable|date',
        ]);

        $old = $announcement->only(['title', 'content', 'type', 'is_active', 'target_group', 'scheduled_at']);

        $announcement->update([
            'title'        => $request->title,
            'content'      => $request->content,
            'type'         => $request->type ?? 'general',
            'is_active'    => $request->boolean('is_active'),
            'target_group' => $request->target_group ?? 'all',
            'scheduled_at' => $request->scheduled_at,
        ]);

        EditorAuditLog::record('updated', 'announcement', $announcement->id, $old, $request->only(['title', 'type', 'target_group']));

        return back()->with('success', 'Announcement updated!');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        EditorAuditLog::record('deleted', 'announcement', $announcement->id, $announcement->only(['title', 'type']));
        $announcement->delete();

        return back()->with('success', 'Announcement deleted.');
    }

    public function toggle(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['is_active' => ! $announcement->is_active]);

        EditorAuditLog::record(
            $announcement->is_active ? 'activated' : 'deactivated',
            'announcement',
            $announcement->id
        );

        return back()->with('success', 'Status updated.');
    }
}