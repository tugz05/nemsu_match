<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountNotDisabled
{
    /**
     * Redirect disabled users to the disabled-account page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        // Allow disabled-account page and appeal submission itself.
        if ($request->routeIs('account.disabled') || $request->routeIs('account.disabled.appeal')) {
            return $next($request);
        }

        if ($user->is_disabled) {
            return redirect()->route('account.disabled');
        }

        return $next($request);
    }
}
