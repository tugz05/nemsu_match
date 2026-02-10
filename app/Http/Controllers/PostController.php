<?php

namespace App\Http\Controllers;

use App\Events\NotificationSent;
use App\Models\Notification;
use App\Models\Post;
use App\Models\PostComment;
use App\Models\PostReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Get all posts for feed
     */
    public function index(Request $request)
    {
        $perPage = min((int) $request->input('per_page', 15), 30);
        $posts = Post::with(['user'])
            ->latest()
            ->paginate($perPage);

        $payload = $posts->toArray();
        $userIds = collect($payload['data'])->pluck('user_id')->unique()->values()->all();

        $followingIds = Auth::user()->following()
            ->whereIn('following_id', $userIds)
            ->pluck('following_id')
            ->all();

        $authId = Auth::id();
        foreach ($payload['data'] as &$item) {
            $item['is_followed_by_user'] = in_array($item['user_id'], $followingIds);
            $item['is_own_post'] = (int) $item['user_id'] === (int) $authId;
        }

        $payload['current_user_id'] = $authId;

        return response()->json($payload);
    }

    /**
     * Get a single post (for post view page / sharing / comment anchor)
     */
    public function show(Post $post)
    {
        $post->load('user');
        $authId = Auth::id();
        $item = $post->toArray();
        $item['is_followed_by_user'] = Auth::user()->isFollowing($post->user);
        $item['is_own_post'] = (int) $post->user_id === (int) $authId;
        return response()->json(['post' => $item]);
    }

    /**
     * Store a new post (supports 1 or more images)
     */
    public function store(Request $request)
    {
        $request->validate([
            // Allow extra room server-side so 1000 visible chars (with newlines)
            // never trip validation due to CRLF/encoding differences.
            'content' => 'required|string|max:1500',
            'image' => 'nullable|image|max:5120',
            'images' => 'nullable|array',
            'images.*' => 'image|max:5120',
        ]);

        $validated = [
            'user_id' => Auth::id(),
            'content' => $request->input('content'),
        ];

        $imagePaths = [];

        // Multiple images (images[])
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                $imagePaths[] = $file->store('post-images', 'public');
            }
        }
        // Single image (legacy)
        if ($request->hasFile('image')) {
            $imagePaths[] = $request->file('image')->store('post-images', 'public');
        }

        if (count($imagePaths) > 0) {
            $validated['images'] = $imagePaths;
            $validated['image'] = $imagePaths[0]; // keep first for backward compat
        }

        $post = Post::create($validated);

        return response()->json([
            'message' => 'Post created successfully!',
            'post' => $post->load('user'),
        ], 201);
    }

    /**
     * Toggle like on a post
     */
    public function toggleLike(Post $post)
    {
        $liked = $post->toggleLike(Auth::id());

        if ($liked) {
            $notification = Notification::notify($post->user_id, 'like', Auth::id(), 'post', $post->id);
            if ($notification) {
                broadcast(new NotificationSent($notification));
            }
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $post->likes_count,
        ]);
    }

    /**
     * Get comments for a post (top-level with nested replies)
     */
    public function getComments(Post $post)
    {
        $comments = $post->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies' => fn ($q) => $q->with('user')])
            ->latest()
            ->get();

        return response()->json($comments);
    }

    /**
     * Add a comment (or reply) to a post
     */
    public function addComment(Request $request, Post $post)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:500',
            'parent_id' => 'nullable|integer|exists:post_comments,id',
        ]);

        $parentId = $validated['parent_id'] ?? null;
        if ($parentId) {
            $parent = PostComment::where('id', $parentId)->where('post_id', $post->id)->firstOrFail();
        }

        $comment = $post->comments()->create([
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'parent_id' => $parentId,
        ]);

        // Update post comment count
        $post->increment('comments_count');

        $notificationData = [
            'comment_id' => $comment->id,
            'excerpt' => \Str::limit($validated['content'], 80),
        ];
        if ($parentId && isset($parent)) {
            $notificationData['is_reply'] = true;
            $notificationData['is_reply_to_you'] = (int) $parent->user_id === (int) $post->user_id;
        }
        $notification = Notification::notify($post->user_id, 'comment', Auth::id(), 'post', $post->id, $notificationData);
        if ($notification) {
            broadcast(new NotificationSent($notification));
        }

        return response()->json([
            'message' => 'Comment added successfully!',
            'comment' => $comment->load('user'),
        ], 201);
    }

    /**
     * Toggle like on a comment
     */
    public function toggleCommentLike(Post $post, PostComment $comment)
    {
        if ($comment->post_id !== $post->id) {
            return response()->json(['message' => 'Comment does not belong to this post'], 403);
        }

        $liked = $comment->toggleLike(Auth::id());

        if ($liked) {
            $notification = Notification::notify($comment->user_id, 'comment_like', Auth::id(), 'comment', $comment->id, [
                'post_id' => $post->id,
                'excerpt' => \Str::limit($comment->content, 60),
            ]);
            if ($notification) {
                broadcast(new NotificationSent($notification));
            }
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $comment->fresh()->likes_count,
        ]);
    }

    /**
     * Toggle repost on a post
     */
    public function toggleRepost(Post $post)
    {
        $userId = Auth::id();

        // Check if user already reposted
        $existingRepost = \DB::table('post_reposts')
            ->where('user_id', $userId)
            ->where('post_id', $post->id)
            ->first();

        if ($existingRepost) {
            // Remove repost
            \DB::table('post_reposts')
                ->where('user_id', $userId)
                ->where('post_id', $post->id)
                ->delete();

            $post->decrement('reposts_count');
            $reposted = false;
        } else {
            // Add repost
            \DB::table('post_reposts')->insert([
                'user_id' => $userId,
                'post_id' => $post->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $post->increment('reposts_count');
            $reposted = true;
        }

        return response()->json([
            'reposted' => $reposted,
            'reposts_count' => $post->reposts_count,
        ]);
    }

    /**
     * Update a post (creator only)
     */
    public function update(Request $request, Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1500',
        ]);

        $post->update(['content' => $validated['content']]);

        return response()->json([
            'message' => 'Post updated successfully!',
            'post' => $post->fresh()->load('user'),
        ]);
    }

    /**
     * Delete a post
     */
    public function destroy(Post $post)
    {
        // Check if user owns the post
        if ($post->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Delete all images (images array and legacy single image)
        $images = $post->images_list ?? [];
        foreach ($images as $path) {
            Storage::disk('public')->delete($path);
        }
        if ($post->image && !in_array($post->image, $images)) {
            Storage::disk('public')->delete($post->image);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully!']);
    }

    /**
     * Report a post
     */
    public function report(Request $request, Post $post)
    {
        $validated = $request->validate([
            'reason' => 'required|string|in:spam,harassment,inappropriate,misleading,other',
            'description' => 'nullable|string|max:500',
        ]);

        // Check if user already reported this post
        $existingReport = PostReport::where('user_id', Auth::id())
            ->where('post_id', $post->id)
            ->where('status', 'pending')
            ->first();

        if ($existingReport) {
            return response()->json([
                'message' => 'You have already reported this post.',
            ], 409);
        }

        PostReport::create([
            'user_id' => Auth::id(),
            'post_id' => $post->id,
            'reason' => $validated['reason'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Post reported successfully. We will review it shortly.',
        ], 201);
    }
}
