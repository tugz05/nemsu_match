<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfilePictureUploaded
{
    /**
     * Require authenticated users to upload a profile picture before using
     * browse/discover features.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $hasProfilePicture = filled($user->profile_picture);
        if ($hasProfilePicture) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Please upload a profile picture before accessing Browse and Discover.',
                'code' => 'profile_picture_required',
            ], 403);
        }

        return redirect()->route('account', ['require_profile_picture' => 1])->with(
            'error',
            'Please upload a profile picture first to access Browse and Discover.'
        );
    }
}

