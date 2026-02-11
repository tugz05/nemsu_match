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
     * Redirect to Google OAuth (NEMSU Workspace or personal Google account).
     *
     * We no longer hard-restrict the domain here so users can choose either:
     * - NEMSU Google Workspace account (@nemsu.edu.ph) → auto-verified
     * - Personal Google account (e.g. @gmail.com)     → will be asked for NEMSU ID
     */
    public function redirect()
    {
        $clientId = config('services.google.client_id');
        $redirectUri = config('services.google.redirect');

        // Build Google OAuth URL
        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
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

            $email = $googleUser['email'];
            $isWorkspace = Str::endsWith($email, '@nemsu.edu.ph');

            // Allow NEMSU Workspace and personal Gmail; reject other domains.
            if (! Str::endsWith($email, ['@nemsu.edu.ph', '@gmail.com'])) {
                return redirect()->route('home')->withErrors([
                    'email' => 'Please sign in using your NEMSU Google Workspace account (@nemsu.edu.ph) or personal Gmail account (@gmail.com).',
                ]);
            }

            // Find or create user
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $googleUser['name'] ?? 'NEMSU Student',
                    'nemsu_id' => $googleUser['id'],
                    'is_workspace_verified' => $isWorkspace,
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)),
                ]
            );

            // If the user already existed and we now know they used a workspace account, mark them as verified.
            if ($isWorkspace && ! $user->is_workspace_verified) {
                $user->forceFill(['is_workspace_verified' => true])->save();
            }

            // Log the user in
            Auth::login($user, true);

            // If this is a personal account (not workspace-verified) and student ID is missing,
            // require the NEMSU ID step before profile setup.
            if (! $user->is_workspace_verified && ! $user->student_id) {
                return redirect()->route('student-id.show');
            }

            // Check if profile is completed
            if (! $user->profile_completed) {
                return redirect()->route('profile.setup')->with('success', 'Welcome! Please complete your profile to continue.');
            }

            // Check if terms have been accepted (required before using the app)
            if (!$user->terms_accepted_at) {
                return redirect()->route('consent.show');
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
