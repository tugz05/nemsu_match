<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\EditorRole;

class EnsureEditor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            if ($request->header('X-Inertia-Prefetch')) {
                return response('', 204);
            }
            return redirect()->route('admin.login');
        }

        $user = auth()->user();

        // Superadmins and admins can also access the editor panel
        if ($user->is_superadmin || $user->is_admin) {
            return $next($request);
        }

        if (!EditorRole::where('user_id', $user->id)->exists()) {
            if ($request->header('X-Inertia-Prefetch')) {
                return response('', 204);
            }
            abort(403, 'Access denied. Editor privileges required.');
        }

        return $next($request);
    }
}