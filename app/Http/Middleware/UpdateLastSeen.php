<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Update the authenticated user's last_seen_at, throttled to once per minute.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = Auth::user();
        if (! $user) {
            return $response;
        }

        $key = 'last_seen:'.$user->id;
        if (Cache::has($key)) {
            return $response;
        }

        Cache::put($key, true, now()->addMinute());
        $user->update(['last_seen_at' => now()]);

        return $response;
    }
}
