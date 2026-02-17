<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProfileCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Step 1: Profile must be completed first
        if (! $user->profile_completed) {
            if (! $request->routeIs('profile.setup') && ! $request->routeIs('profile.store')) {
                return redirect()->route('profile.setup');
            }

            return $next($request);
        }

        // Step 2: Terms and conditions must be accepted before using the app
        if (! $user->terms_accepted_at) {
            if (! $request->routeIs('consent.show') && ! $request->routeIs('consent.accept')) {
                return redirect()->route('consent.show');
            }

            return $next($request);
        }

        return $next($request);
    }
}
