<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    /**
     * List announcements (JSON for public feed)
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 10), 50);

        $announcements = Announcement::with('creator:id,display_name,fullname,profile_picture')
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($announcements);
    }

    /**
     * Store a new Announcement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:5000',
            'is_pinned' => 'sometimes|boolean',
            'publish_now' => 'sometimes|boolean',
        ]);

        $publishNow = $request->boolean('publish_now', true);

        Announcement::create([
            'created_by' => Auth::id(),
            'title' => $validated['title'],
            'body' => $validated['body'],
            'is_pinned' => $request->boolean('is_pinned'),
            'published_at' => $publishNow ? now() : null,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Delete an Announcement
     */
    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return response()->json(['success' => true]);
    }
}
