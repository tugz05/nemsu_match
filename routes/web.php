<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\Api\AutocompleteController;
use App\Http\Controllers\Auth\NEMSUOAuthController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\MatchmakingController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ConsentController;
use App\Http\Controllers\ProfileSetupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\StudentIdController; 
use App\Http\Controllers\Admin\ReportController; 
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

// Admin Authentication Routes
Route::get('admin/login', [AdminAuthController::class, 'showLogin'])->name('admin.login');
Route::post('admin/login', [AdminAuthController::class, 'login']);
Route::post('admin/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

// Profile Setup & Student ID Routes
Route::middleware(['auth'])->group(function () {
    // Student ID step (for personal Google accounts)
    Route::get('student-id', [StudentIdController::class, 'show'])->name('student-id.show');
    Route::post('student-id', [StudentIdController::class, 'store'])->name('student-id.store');

    // Profile setup (after student ID step when required)
    Route::get('profile/setup', [ProfileSetupController::class, 'show'])->name('profile.setup');
    Route::post('profile/setup', [ProfileSetupController::class, 'store'])->name('profile.store');
    Route::put('profile/setup', [ProfileSetupController::class, 'update'])->name('profile-setup.update');

    // Consent & Terms (after profile setup, before app access)
    Route::get('consent', [ConsentController::class, 'show'])->name('consent.show');
    Route::post('consent', [ConsentController::class, 'accept'])->name('consent.accept');

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

// Browse - Suggested matches list
Route::get('browse', function () {
    return Inertia::render('Browse');
})->middleware(['auth', 'verified', 'profile.completed'])->name('browse');

// NEMSU Match Plus - Upgrade page (subscription)
Route::get('plus', function () {
    $price = \App\Models\Superadmin\AppSetting::get('plus_monthly_price', 49);
    $freemiumEnabled = \App\Models\User::freemiumEnabled();
    return Inertia::render('Plus', [
        'plus_monthly_price' => (int) $price,
        'freemium_enabled' => $freemiumEnabled,
    ]);
})->middleware(['auth', 'verified', 'profile.completed'])->name('plus');

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

// Account update route
Route::post('api/account/update', [AccountController::class, 'update'])
    ->middleware(['auth', 'verified', 'profile.completed'])
    ->name('account.update');

// User search & follow + Matchmaking
Route::middleware(['auth', 'verified', 'profile.completed'])->group(function () {
    Route::get('api/users/search', [UserController::class, 'search'])->name('users.search');
    Route::post('api/users/{user}/follow', [UserController::class, 'toggleFollow'])->name('users.follow');
    Route::post('api/users/{user}/report', [UserController::class, 'report'])->name('users.report');
    Route::get('api/matchmaking', [MatchmakingController::class, 'index'])->name('matchmaking.index');
    Route::get('api/matchmaking/discover', [MatchmakingController::class, 'discover'])->name('matchmaking.discover');
    Route::get('api/matchmaking/freemium-state', [MatchmakingController::class, 'freemiumState'])->name('matchmaking.freemium-state');
    Route::get('api/matchmaking/likes', [MatchmakingController::class, 'likes'])->name('matchmaking.likes');
    Route::get('api/matchmaking/who-liked-me-count', [MatchmakingController::class, 'whoLikedMeCount'])->name('matchmaking.who-liked-me-count');
    Route::get('api/matchmaking/who-liked-me', [MatchmakingController::class, 'whoLikedMe'])->name('matchmaking.who-liked-me');
    Route::get('api/matchmaking/my-recent-likes', [MatchmakingController::class, 'myRecentLikes'])->name('matchmaking.my-recent-likes');
    Route::get('api/matchmaking/mutual', [MatchmakingController::class, 'mutualMatches'])->name('matchmaking.mutual');
    Route::post('api/matchmaking/action', [MatchmakingController::class, 'action'])->name('matchmaking.action');

    // Gallery (Account + Profile viewing)
    Route::get('api/gallery', [GalleryController::class, 'index'])->name('gallery.index');
    Route::post('api/gallery', [GalleryController::class, 'store'])->name('gallery.store');
    Route::delete('api/gallery/{id}', [GalleryController::class, 'destroy'])->name('gallery.destroy');

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

// Single post view
Route::get('post/{post}', function (\App\Models\Post $post) {
    return Inertia::render('PostView', [
        'postId' => $post->id,
        'commentId' => request()->query('comment'), 
    ]);
})->middleware(['auth', 'verified', 'profile.completed'])->name('post.show');

// Posts routes
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

// Superadmin routes
Route::middleware(['auth', 'verified', 'superadmin'])->prefix('superadmin')->name('superadmin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Superadmin\DashboardController::class, 'index'])->name('dashboard');

    // Admin Management
    Route::get('admins', [\App\Http\Controllers\Superadmin\AdminController::class, 'index'])->name('admins.index');
    Route::post('admins', [\App\Http\Controllers\Superadmin\AdminController::class, 'store'])->name('admins.store');
    Route::put('admins/{adminRole}', [\App\Http\Controllers\Superadmin\AdminController::class, 'update'])->name('admins.update');
    Route::delete('admins/{adminRole}', [\App\Http\Controllers\Superadmin\AdminController::class, 'destroy'])->name('admins.destroy');
    Route::get('admins/search-users', [\App\Http\Controllers\Superadmin\AdminController::class, 'searchUsers'])->name('admins.search-users');

    // User Management
    Route::get('users', [\App\Http\Controllers\Superadmin\UserController::class, 'index'])->name('users.index');
    Route::get('users/{user}', [\App\Http\Controllers\Superadmin\UserController::class, 'show'])->name('users.show');
    Route::put('users/{user}', [\App\Http\Controllers\Superadmin\UserController::class, 'update'])->name('users.update');
    Route::post('users/{user}/toggle-status', [\App\Http\Controllers\Superadmin\UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('users/{user}', [\App\Http\Controllers\Superadmin\UserController::class, 'destroy'])->name('users.destroy');

    // App Settings
    Route::get('settings', [\App\Http\Controllers\Superadmin\SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [\App\Http\Controllers\Superadmin\SettingsController::class, 'store'])->name('settings.store');
    Route::put('settings/{appSetting}', [\App\Http\Controllers\Superadmin\SettingsController::class, 'update'])->name('settings.update');
    Route::delete('settings/{appSetting}', [\App\Http\Controllers\Superadmin\SettingsController::class, 'destroy'])->name('settings.destroy');
});

// Fixed Admin & Report Routes Section
Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('dashboard');
        Route::get('message-report', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('message.report');
        Route::put('message-report/{id}', [\App\Http\Controllers\Admin\ReportController::class, 'update'])->name('message.report.update');
        Route::delete('message-report/{id}', [\App\Http\Controllers\Admin\ReportController::class, 'destroy'])->name('message.report.destroy');
    });