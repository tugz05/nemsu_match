<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ConsentController extends Controller
{
    /**
     * Show the consent and terms & conditions page.
     */
    public function show(): Response
    {
        return Inertia::render('Consent');
    }

    /**
     * Record that the user accepted terms and redirect to the app.
     */
    public function accept(Request $request)
    {
        $request->validate([
            'accepted' => 'required|accepted',
        ], [
            'accepted.accepted' => 'You must agree to the Terms and Conditions to continue.',
        ]);

        $user = Auth::user();
        if ($user) {
            $user->update(['terms_accepted_at' => now()]);
        }

        return redirect()->route('browse')->with('success', 'Welcome to NEMSU Match!');
    }
}
