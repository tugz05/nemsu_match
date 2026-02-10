<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class NEMSUOAuthController extends Controller
{
    /**
     * Show the NEMSU Match login page
     */
    public function showLogin()
    {
        // If user is already authenticated, redirect to browse
        if (Auth::check()) {
            return redirect()->route('browse');
        }

        return Inertia::render('auth/NEMSULogin');
    }

    /**
     * Redirect to Google OAuth with NEMSU domain restriction
     */
    public function redirect()
    {
        $clientId = config('services.google.client_id');
        $redirectUri = config('services.google.redirect');
        $nemsuDomain = config('services.nemsu.domain', 'nemsu.edu.ph');

        // Build Google OAuth URL with NEMSU domain restriction
        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'hd' => $nemsuDomain, // Restrict to NEMSU domain
            'prompt' => 'select_account', // Force account selection
            'access_type' => 'online',
            'state' => csrf_token(), // CSRF protection
        ];

        $googleAuthUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);

        return redirect($googleAuthUrl);
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback(Request $request)
    {
        try {
            // Get authorization code from Google
            $code = $request->input('code');

            if (!$code) {
                return redirect()->route('home')->withErrors([
                    'error' => 'Authorization failed. Please try again.',
                ]);
            }

            // Exchange code for access token
            $tokenResponse = \Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => config('services.google.redirect'),
                'grant_type' => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to obtain access token');
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];

            // Get user info from Google
            $userResponse = \Http::withToken($accessToken)->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if (!$userResponse->successful()) {
                throw new \Exception('Failed to get user info');
            }

            $googleUser = $userResponse->json();

            // Validate NEMSU email domain
            $email = $googleUser['email'];
            if (!Str::endsWith($email, '@nemsu.edu.ph')) {
                return redirect()->route('home')->withErrors([
                    'email' => 'Only NEMSU email addresses (@nemsu.edu.ph) are allowed. Please use your NEMSU Google Workspace account.',
                ]);
            }

            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $googleUser['name'] ?? 'NEMSU Student',
                    'nemsu_id' => $googleUser['id'],
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)),
                ]
            );

            // Log the user in
            Auth::login($user, true);

            // Check if profile is completed
            if (!$user->profile_completed) {
                return redirect()->route('profile.setup')->with('success', 'Welcome! Please complete your profile to continue.');
            }

            // Main route when authenticated: Browse (preferences-based list of users)
            return redirect()->route('browse')->with('success', 'Welcome back, ' . ($user->display_name ?? $user->name) . '!');

        } catch (\Exception $e) {
            \Log::error('OAuth callback failed: ' . $e->getMessage());

            return redirect()->route('home')->withErrors([
                'error' => 'Authentication failed: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'You have been logged out successfully.');
    }
}
