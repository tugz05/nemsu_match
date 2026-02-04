<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnnouncementController extends Controller
{
    /**
     * GET /api/announcements?page=1
     * Public (authed) list of announcements; non-admin users only see published items.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $query = Announcement::query()
            ->with('creator:id,display_name,fullname,profile_picture')
            ->orderByDesc('is_pinned')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if (! ($user->is_admin ?? false)) {
            $query->whereNotNull('published_at')
                ->where('published_at', '<=', now());
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $data = collect($paginator->items())->map(function (Announcement $a): array {
            return [
                'id' => $a->id,
                'title' => $a->title,
                'body' => $a->body,
                'is_pinned' => (bool) $a->is_pinned,
                'published_at' => $a->published_at?->toIso8601String(),
                'created_at' => $a->created_at?->toIso8601String(),
                'creator' => $a->creator ? [
                    'id' => $a->creator->id,
                    'display_name' => $a->creator->display_name,
                    'fullname' => $a->creator->fullname,
                    'profile_picture' => $a->creator->profile_picture,
                ] : null,
            ];
        })->values()->all();

        return response()->json([
            'data' => $data,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ]);
    }

    /**
     * POST /api/announcements
     * Admin-only create announcement (defaults to publish now).
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:120',
            'body' => 'required|string|max:5000',
            'is_pinned' => 'sometimes|boolean',
            'publish_now' => 'sometimes|boolean',
        ]);

        $user = Auth::user();

        $publishNow = (bool) ($request->input('publish_now', true));

        $a = Announcement::create([
            'created_by' => $user->id,
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'is_pinned' => (bool) ($request->input('is_pinned', false)),
            'published_at' => $publishNow ? now() : null,
        ]);

        return response()->json([
            'id' => $a->id,
            'ok' => true,
        ], 201);
    }
}

