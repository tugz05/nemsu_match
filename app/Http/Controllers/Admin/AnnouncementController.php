<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AnnouncementController extends Controller
{
    /**
     * Show the admin announcements management page.
     */
    public function index(Request $request): Response
    {
        $perPage = min((int) $request->input('per_page', 10), 50);

        $announcements = Announcement::with('creator:id,display_name')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Admin/Announcements', [
            'announcements' => $announcements,
        ]);
    }

    /**
     * Create a new announcement.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:5000',
            'is_pinned' => 'sometimes|boolean',
            'publish_now' => 'sometimes|boolean',
        ]);

        Announcement::create([
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => $request->boolean('publish_now', true) ? now() : null,
        ]);

        return back()->with('success', 'Announcement created.');
    }

    /**
     * Update an announcement.
     */
    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:5000',
            'is_pinned' => 'sometimes|boolean',
            'publish_now' => 'sometimes|boolean',
        ]);

        $announcement->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => $request->boolean('publish_now', true) ? ($announcement->published_at ?? now()) : null,
        ]);

        return back()->with('success', 'Announcement updated.');
    }

    /**
     * Delete an announcement.
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }
}
