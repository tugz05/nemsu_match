<?php

namespace App\Http\Middleware;

use App\Models\Superadmin\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Inertia\Inertia;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip check for admin routes
        if ($request->is('admin/*') || $request->is('superadmin/*')) {
            return $next($request);
        }

        // Check if maintenance mode is enabled
        $maintenanceMode = AppSetting::get('maintenance_mode', false);

        if ($maintenanceMode) {
            // Show maintenance page for all non-admin routes (authenticated or guests)
            return Inertia::render('MaintenancePage')->toResponse($request);
        }

        return $next($request);
    }
}
