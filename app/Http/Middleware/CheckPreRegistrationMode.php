<?php

namespace App\Http\Middleware;

use App\Models\Superadmin\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

class CheckPreRegistrationMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for admin and superadmin route prefixes (management interfaces)
        if ($request->is('admin/*') || $request->is('superadmin/*')) {
            return $next($request);
        }

        // Allow essential authentication, home login, and profile-setup routes even in pre-registration mode
        if (
            $request->routeIs('home')
            || $request->routeIs('nemsu.login')
            || $request->routeIs('oauth.nemsu.redirect')
            || $request->routeIs('oauth.nemsu.callback')
            || $request->routeIs('nemsu.logout')
            || $request->routeIs('profile.setup')
            || $request->routeIs('profile.store')
            || $request->routeIs('profile-setup.update')
            || $request->routeIs('consent.show')
            || $request->routeIs('consent.accept')
        ) {
            return $next($request);
        }

        // Check if pre-registration mode is enabled
        $preRegistrationMode = AppSetting::get('pre_registration_mode', false);
        $allowRegistration = AppSetting::get('allow_registration', true);

        if ($preRegistrationMode || !$allowRegistration) {
            $baseQuery = \App\Models\User::where('is_admin', false)->where('is_superadmin', false);

            // Count pre-registered users (only regular users, not admins)
            $preRegisteredCount = (clone $baseQuery)->count();

            // Count by gender (Female, Male, Lesbian, Gay)
            $preRegisteredFemale = (clone $baseQuery)->where('gender', 'Female')->count();
            $preRegisteredMale = (clone $baseQuery)->where('gender', 'Male')->count();
            $preRegisteredLesbian = (clone $baseQuery)->where('gender', 'Lesbian')->count();
            $preRegisteredGay = (clone $baseQuery)->where('gender', 'Gay')->count();

            // Show pre-registration page for guests
            return Inertia::render('PreRegistration', [
                'preRegistrationMode' => $preRegistrationMode,
                'allowRegistration' => $allowRegistration,
                'preRegisteredCount' => $preRegisteredCount,
                'preRegisteredFemale' => $preRegisteredFemale,
                'preRegisteredMale' => $preRegisteredMale,
                'preRegisteredLesbian' => $preRegisteredLesbian,
                'preRegisteredGay' => $preRegisteredGay,
            ])->toResponse($request);
        }

        return $next($request);
    }
}
