<?php

namespace App\Http\Middleware;

use App\Models\Superadmin\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     * When maintenance mode is ON, only regular users see the maintenance page.
     * Superadmin, admin, and editor can still use the app.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for admin/superadmin management routes
        if ($request->is('admin/*') || $request->is('superadmin/*')) {
            return $next($request);
        }

        $maintenanceMode = AppSetting::get('maintenance_mode', false);

        if ($maintenanceMode) {
            $user = Auth::user();
            if ($user && $user->isStaff()) {
                return $next($request);
            }
            return Inertia::render('MaintenancePage')->toResponse($request);
        }

        return $next($request);
    }
}
