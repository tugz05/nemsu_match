<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\EditorRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login page
     */
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->is_superadmin) {
                return Inertia::location('/superadmin');
            }

            if ($user->is_admin) {
                return Inertia::location('/admin/dashboard');
            }

            if (EditorRole::where('user_id', $user->id)->exists()) {
                return Inertia::location('/editor/dashboard');
            }

            // Regular user — log them out and show login
            Auth::logout();
        }

        return Inertia::render('auth/AdminLogin');
    }

    /**
     * Handle admin login with email and password
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Rate limiting
        $key = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check if user exists
        if (! $user) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'No account found with this email address.',
            ]);
        }

        // Check if user has admin, superadmin, or editor privileges
        $isEditor = EditorRole::where('user_id', $user->id)->exists();

        if (! $user->is_admin && ! $user->is_superadmin && ! $isEditor) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'This account does not have administrative privileges.',
            ]);
        }

        // Verify password
        if (! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        // Clear rate limiter
        RateLimiter::clear($key);

        // Log the user in
        Auth::login($user, $request->boolean('remember'));

        $request->session()->regenerate();

        // Redirect based on role — order matters: superadmin > admin > editor
        if ($user->is_superadmin) {
            return redirect()->intended(route('superadmin.dashboard'))
                ->with('success', 'Welcome back, Superadmin!');
        }

        if ($user->is_admin) {
            return redirect()->intended(route('admin.dashboard'))
                ->with('success', 'Welcome back, Admin!');
        }

        // Editor — use direct redirect, NOT intended() to avoid session URL overriding
        return redirect('/editor/dashboard')
            ->with('success', 'Welcome back, Editor!');
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('status', 'You have been logged out successfully.');
    }
}