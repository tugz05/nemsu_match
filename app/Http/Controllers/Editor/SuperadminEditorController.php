<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\EditorRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EditorController extends Controller
{
    public function index(): Response
    {
        $editors = EditorRole::with('user:id,display_name,email,profile_picture,created_at')
            ->latest()
            ->paginate(20);

        return Inertia::render('Superadmin/Editors', [
            'editors' => $editors,
        ]);
    }

    public function searchUsers(Request $request)
    {
        $query = $request->get('q', '');

        $users = User::where('display_name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->whereDoesntHave('editorRole')
            ->take(10)
            ->get(['id', 'display_name', 'email', 'profile_picture']);

        return response()->json($users);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        EditorRole::firstOrCreate(
            ['user_id' => $request->user_id],
            ['granted_by' => auth()->user()->display_name ?? auth()->user()->email]
        );

        return back()->with('success', 'Editor role granted successfully.');
    }

    public function destroy(EditorRole $editorRole): RedirectResponse
    {
        $editorRole->delete();

        return back()->with('success', 'Editor role removed.');
    }
}