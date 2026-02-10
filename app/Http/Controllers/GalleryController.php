<?php

namespace App\Http\Controllers;

use App\Models\UserGalleryPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class GalleryController extends Controller
{
    /**
     * List gallery photos.
     *
     * If ?user_id= is provided, returns that user's public gallery (for profile viewing).
     * Otherwise, returns the authenticated user's own gallery.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();
        $userId = $request->query('user_id');

        $query = UserGalleryPhoto::query();
        if ($userId) {
            // Viewing someone else's profile – filter by that user_id
            $query->where('user_id', (int) $userId);
        } else {
            // Default: current user's gallery
            $query->where('user_id', $authUser->id);
        }

        $photos = $query->orderByDesc('created_at')->get()->map(fn (UserGalleryPhoto $p) => [
            'id' => $p->id,
            'path' => $p->path,
            'url' => '/storage/' . $p->path,
            'created_at' => $p->created_at->toIso8601String(),
        ]);

        return response()->json(['data' => $photos]);
    }

    /**
     * Upload one or more photos to the user's gallery.
     * Accepts: "photo" (single file) or "photos[]" (multiple files).
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->hasFile('photos')) {
            $request->validate([
                'photos' => 'array',
                'photos.*' => 'file|image|max:5120', // 5MB each
            ]);
            $files = $request->file('photos');
        } elseif ($request->hasFile('photo')) {
            $request->validate(['photo' => 'required|file|image|max:5120']);
            $files = [$request->file('photo')];
        } else {
            return response()->json(['message' => 'No photo(s) provided.'], 422);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $created = [];
        foreach ($files as $file) {
            $path = $file->store('gallery/' . $user->id, 'public');
            $photo = $user->galleryPhotos()->create(['path' => $path]);
            $created[] = [
                'id' => $photo->id,
                'path' => $photo->path,
                'url' => '/storage/' . $photo->path,
                'created_at' => $photo->created_at->toIso8601String(),
            ];
        }

        return response()->json(['data' => $created], 201);
    }

    /**
     * Delete a gallery photo (must belong to current user).
     */
    public function destroy(int $id): JsonResponse
    {
        $gallery = UserGalleryPhoto::where('user_id', Auth::id())->findOrFail($id);
        Storage::disk('public')->delete($gallery->path);
        $gallery->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
