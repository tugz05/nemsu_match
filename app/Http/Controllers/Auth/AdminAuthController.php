<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login page
     */
    public function showLogin(): Response
    {
        // If user is already authenticated and is admin/superadmin, redirect to appropriate dashboard
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->is_superadmin) {
                return Inertia::location('/superadmin');
            }
            
            if ($user->is_admin) {
                return Inertia::location('/admin/dashboard');
            }
            
            // If regular user, logout and show login page
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
        if (!$user) {
            RateLimiter::hit($key, 60);
            
            throw ValidationException::withMessages([
                'email' => 'No admin account found with this email address.',
            ]);
        }

        // Check if user has admin privileges
        if (!$user->is_admin && !$user->is_superadmin) {
            RateLimiter::hit($key, 60);
            
            throw ValidationException::withMessages([
                'email' => 'This account does not have administrative privileges.',
            ]);
        }

        // Attempt to authenticate
        if (!Hash::check($request->password, $user->password)) {
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

        // Redirect based on role
        if ($user->is_superadmin) {
            return redirect()->intended(route('superadmin.dashboard'))
                ->with('success', 'Welcome back, Superadmin!');
        }

        return redirect()->intended(route('admin.dashboard'))
            ->with('success', 'Welcome back, Admin!');
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('status', 'You have been logged out successfully.');
    }
}
