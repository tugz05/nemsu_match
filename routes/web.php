<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Api\AutocompleteController;
use App\Http\Controllers\Auth\NEMSUOAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\MatchmakingController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

// Home route - Show NEMSU Match login page
Route::get('/', [NEMSUOAuthController::class, 'showLogin'])->name('home');

// NEMSU Match Authentication Routes
Route::get('nemsu/login', [NEMSUOAuthController::class, 'showLogin'])->name('nemsu.login');
Route::get('oauth/nemsu/redirect', [NEMSUOAuthController::class, 'redirect'])->name('oauth.nemsu.redirect');
Route::get('oauth/nemsu/callback', [NEMSUOAuthController::class, 'callback'])->name('oauth.nemsu.callback');
Route::post('nemsu/logout', [NEMSUOAuthController::class, 'logout'])->name('nemsu.logout');

// Profile Setup Routes
Route::middleware(['auth'])->group(function () {
    Route::get('profile/setup', [ProfileSetupController::class, 'show'])->name('profile.setup');
    Route::post('profile/setup', [ProfileSetupController::class, 'store'])->name('profile.store');
    Route::put('profile/setup', [ProfileSetupController::class, 'update'])->name('profile-setup.update');

    // Autocomplete API endpoints
    Route::get('api/autocomplete/academic-programs', [AutocompleteController::class, 'academicPrograms'])->name('api.autocomplete.programs');
    Route::get('api/autocomplete/courses', [AutocompleteController::class, 'courses'])->name('api.autocomplete.courses');
    Route::get('api/autocomplete/interests', [AutocompleteController::class, 'interests'])->name('api.autocomplete.interests');
});

// Home/Feed route - Social feed like Threads
Route::get('home', function () {
    return Inertia::render('Home', [
        'user' => Auth::user()->only(['id', 'display_name', 'profile_picture']),
    ]);
})->middleware(['auth', 'verified', 'profile.completed'])->name('home.feed');

// Dashboard route - Discover/Match
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'profile.completed'])->name('dashboard');

// Like You - Matchmaking (Discover)
Route::get('like-you', function () {
    return Inertia::render('LikeYou', [
        'user' => Auth::user()->only(['id', 'display_name', 'profile_picture']),
    ]);
})->middleware(['auth', 'verified', 'profile.completed'])->name('likeyou');

// Announcements
Route::get('announcements', function () {
    return Inertia::render('Announcements', [
        'user' => Auth::user()->only(['id', 'display_name', 'profile_picture', 'is_admin']),
    ]);
})->middleware(['auth', 'verified', 'profile.completed'])->name('announcements');

// Profile search page (full-page search like social apps)
Route::get('search', function () {
    return Inertia::render('Search', [
        'q' => request()->query('q'),
    ]);
})->middleware(['auth', 'verified', 'profile.completed'])->name('search');

// Account route
Route::get('account', [AccountController::class, 'show'])
    ->middleware(['auth', 'verified', 'profile.completed'])
    ->name('account');

// Profile viewing (other users; own profile redirects to account)
Route::get('profile/{user}', [ProfileController::class, 'show'])
    ->middleware(['auth', 'verified', 'profile.completed'])
    ->name('profile.show');

// Debug: see what array data the account page receives (remove in production)
Route::get('account-debug-arrays', function () {
    $user = Auth::user();
    $row = DB::table('users')->where('id', $user->id)->first();
    $decode = function($raw) {
        if ($raw === null || $raw === '') return [];
        if (is_array($raw)) return array_values(array_filter($raw, 'is_string'));
        if (!is_string($raw)) return [];
        $d = json_decode($raw, true);
        return is_array($d) ? array_values(array_filter($d, 'is_string')) : [];
    };
    return response()->json([
        'user_id' => $user->id,
        'raw_extracurricular' => $row->extracurricular_activities ?? null,
        'raw_academic_goals' => $row->academic_goals ?? null,
        'raw_interests' => $row->interests ?? null,
        'decoded_extracurricular_activities' => $decode($row->extracurricular_activities ?? null),
        'decoded_academic_goals' => $decode($row->academic_goals ?? null),
        'decoded_interests' => $decode($row->interests ?? null),
    ]);
})->middleware(['auth', 'verified', 'profile.completed']);

// Account update route
Route::post('api/account/update', [AccountController::class, 'update'])
    ->middleware(['auth', 'verified', 'profile.completed'])
    ->name('account.update');

// User search & follow + Matchmaking
Route::middleware(['auth', 'verified', 'profile.completed'])->group(function () {
    Route::get('api/users/search', [UserController::class, 'search'])->name('users.search');
    Route::post('api/users/{user}/follow', [UserController::class, 'toggleFollow'])->name('users.follow');
    Route::get('api/matchmaking', [MatchmakingController::class, 'index'])->name('matchmaking.index');
    Route::get('api/matchmaking/likes', [MatchmakingController::class, 'likes'])->name('matchmaking.likes');
    Route::post('api/matchmaking/action', [MatchmakingController::class, 'action'])->name('matchmaking.action');

    Route::get('api/announcements', [AnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('api/announcements', [AnnouncementController::class, 'store'])->middleware('admin')->name('announcements.store');
});

// Chat / Messaging
Route::middleware(['auth', 'verified', 'profile.completed'])->group(function () {
    Route::get('chat', function () {
    return Inertia::render('Chat', [
        'conversationId' => request()->query('conversation'),
        'userId' => request()->query('user'),
    ]);
})->name('chat');
    Route::get('api/conversations', [ChatController::class, 'index'])->name('chat.conversations');
    Route::get('api/conversations/unread-count', [ChatController::class, 'unreadCount'])->name('chat.unread-count');
    Route::get('api/conversations/{conversation}', [ChatController::class, 'show'])->name('chat.conversations.show');
    Route::post('api/conversations', [ChatController::class, 'store'])->name('chat.conversations.store');
    Route::get('api/conversations/{conversation}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('api/conversations/{conversation}/messages', [ChatController::class, 'sendMessage'])->name('chat.send');
    Route::post('api/conversations/send', [ChatController::class, 'sendMessageToUser'])->name('chat.send-to-user');
    Route::post('api/conversations/{conversation}/read', [ChatController::class, 'markRead'])->name('chat.read');
    Route::post('api/conversations/{conversation}/typing', [ChatController::class, 'typing'])->name('chat.typing');
    Route::get('api/message-requests', [ChatController::class, 'messageRequests'])->name('chat.requests');
    Route::post('api/message-requests/{message_request}/accept', [ChatController::class, 'acceptRequest'])->name('chat.requests.accept');
    Route::post('api/message-requests/{message_request}/decline', [ChatController::class, 'declineRequest'])->name('chat.requests.decline');
    Route::post('api/users/{user}/block', [ChatController::class, 'block'])->name('users.block');
    Route::delete('api/users/{user}/block', [ChatController::class, 'unblock'])->name('users.unblock');
    Route::post('api/conversations/{conversation}/report', [ChatController::class, 'reportConversation'])->name('chat.report');
});

// Notifications
Route::middleware(['auth', 'verified', 'profile.completed'])->group(function () {
    Route::get('notifications', fn () => \Inertia\Inertia::render('Notifications'))->name('notifications');
    Route::get('api/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('api/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
    Route::put('api/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('api/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
});

// Single post view (shareable URL, supports ?comment=123 or #comment-123 for anchor)
Route::get('post/{post}', function (\App\Models\Post $post) {
    return Inertia::render('PostView', [
        'postId' => $post->id,
        'commentId' => request()->query('comment'), // optional: scroll to this comment
    ]);
})->middleware(['auth', 'verified', 'profile.completed'])->name('post.show');

// Posts routes (Social feed - Threads-like)
Route::middleware(['auth', 'verified', 'profile.completed'])->group(function () {
    Route::get('api/posts', [PostController::class, 'index'])->name('posts.index');
    Route::get('api/posts/{post}', [PostController::class, 'show'])->name('posts.show');
    Route::post('api/posts', [PostController::class, 'store'])->name('posts.store');
    Route::post('api/posts/{post}/like', [PostController::class, 'toggleLike'])->name('posts.like');
    Route::get('api/posts/{post}/comments', [PostController::class, 'getComments'])->name('posts.comments');
    Route::post('api/posts/{post}/comment', [PostController::class, 'addComment'])->name('posts.comment');
    Route::post('api/posts/{post}/comments/{comment}/like', [PostController::class, 'toggleCommentLike'])->name('posts.comments.like');
    Route::post('api/posts/{post}/repost', [PostController::class, 'toggleRepost'])->name('posts.repost');
    Route::post('api/posts/{post}/report', [PostController::class, 'report'])->name('posts.report');
    Route::put('api/posts/{post}', [PostController::class, 'update'])->name('posts.update');
    Route::delete('api/posts/{post}', [PostController::class, 'destroy'])->name('posts.destroy');
});

// Settings routes removed - will create custom NEMSU Match settings later if needed
