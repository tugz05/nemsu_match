<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class SocialFeedController extends Controller
{
    public function index(): Response
    {
        $posts = Post::with('user:id,display_name,email,profile_picture')
            ->withCount('likes', 'comments')
            ->latest()
            ->paginate(20);

        return Inertia::render('Editor/SocialFeed', [
            'posts' => $posts,
        ]);
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return back()->with('success', 'Post removed.');
    }
}