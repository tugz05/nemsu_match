<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Interest;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AccountController extends Controller
{
    /**
     * Show account page
     */
    public function show()
    {
        $user = Auth::user();

        // Fetch raw row from DB so we get exact JSON strings (bypass Eloquent entirely)
        $row = DB::table('users')->where('id', $user->id)->first();
        if (!$row) {
            return redirect()->route('home');
        }

        $decode = function($raw) {
            if ($raw === null || $raw === '') {
                return [];
            }
            if (is_array($raw)) {
                return array_values(array_filter($raw, 'is_string'));
            }
            if (!is_string($raw)) {
                return [];
            }
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, 'is_string'));
            }
            // Handle double-encoded JSON
            if (is_string($decoded)) {
                $decoded2 = json_decode($decoded, true);
                return is_array($decoded2) ? array_values(array_filter($decoded2, 'is_string')) : [];
            }
            return [];
        };

        $courses = $decode($row->courses ?? null);
        $researchInterests = $decode($row->research_interests ?? null);
        $extracurricularActivities = $decode($row->extracurricular_activities ?? null);
        $academicGoals = $decode($row->academic_goals ?? null);
        $interests = $decode($row->interests ?? null);

        $followingCount = $user->following()->count();
        $followersCount = $user->followers()->count();
        $postsCount = Post::where('user_id', $user->id)->count();
        $memberSince = $user->created_at ? $user->created_at->diffForHumans(null, true) : null;

        return Inertia::render('Account', [
            'user' => [
                'id' => $user->id,
                'display_name' => $user->display_name,
                'fullname' => $user->fullname,
                'email' => $user->email,
                'campus' => $user->campus,
                'academic_program' => $user->academic_program,
                'year_level' => $user->year_level,
                'profile_picture' => $user->profile_picture,
                'bio' => $user->bio,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'courses' => $courses,
                'research_interests' => $researchInterests,
                'extracurricular_activities' => $extracurricularActivities,
                'academic_goals' => $academicGoals,
                'interests' => $interests,
                'following_count' => $followingCount,
                'followers_count' => $followersCount,
                'posts_count' => $postsCount,
                'member_since' => $memberSince,
            ],
        ]);
    }

    /**
     * Update account information
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'fullname' => 'sometimes|string|max:255',
            'campus' => 'sometimes|string|max:255',
            'academic_program' => 'sometimes|string|max:255',
            'year_level' => 'sometimes|string|max:255',
            'bio' => 'sometimes|string|max:500',
            'gender' => 'sometimes|string|max:255',
            'profile_picture' => 'nullable|image|max:5120', // 5MB max
            'courses' => 'sometimes|array',
            'courses.*' => 'string|max:255',
            'research_interests' => 'sometimes|array',
            'research_interests.*' => 'string|max:255',
            'extracurricular_activities' => 'sometimes|array',
            'extracurricular_activities.*' => 'string|max:255',
            'academic_goals' => 'sometimes|array',
            'academic_goals.*' => 'string|max:255',
            'interests' => 'sometimes|array',
            'interests.*' => 'string|max:255',
        ]);

        // Normalize array fields (trim, filter empty)
        $arrayFields = ['courses', 'research_interests', 'extracurricular_activities', 'academic_goals', 'interests'];
        foreach ($arrayFields as $field) {
            if (isset($validated[$field]) && is_array($validated[$field])) {
                $validated[$field] = array_values(array_filter(array_map('trim', $validated[$field])));
            }
        }

        // Update suggestion tables for autocomplete
        if (!empty($validated['courses'])) {
            foreach ($validated['courses'] as $course) {
                Course::incrementOrCreate($course);
            }
        }
        if (!empty($validated['research_interests'])) {
            foreach ($validated['research_interests'] as $interest) {
                Interest::incrementOrCreate($interest, 'research');
            }
        }
        if (!empty($validated['extracurricular_activities'])) {
            foreach ($validated['extracurricular_activities'] as $activity) {
                Interest::incrementOrCreate($activity, 'extracurricular');
            }
        }
        if (!empty($validated['academic_goals'])) {
            foreach ($validated['academic_goals'] as $goal) {
                Interest::incrementOrCreate($goal, 'academic_goal');
            }
        }
        if (!empty($validated['interests'])) {
            foreach ($validated['interests'] as $interest) {
                Interest::incrementOrCreate($interest, 'hobby');
            }
        }

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }
            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            $validated['profile_picture'] = $path;
        }

        // Update user (array fields are cast to JSON by User model)
        $user->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }
}
