<?php

namespace App\Http\Controllers;

use App\Models\AcademicProgram;
use App\Models\Course;
use App\Models\Interest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileSetupController extends Controller
{
    /**
     * Show the profile setup page
     */
    public function show(): Response
    {
        return Inertia::render('profile/ProfileSetup', [
            'user' => Auth::user(),
        ]);
    }

    /**
     * Store the profile setup data
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validation with array support for tags
        $validated = $request->validate([
            'display_name' => 'required|string|max:255|unique:users,display_name,' . $user->id,
            'fullname' => 'required|string|max:255|unique:users,fullname,' . $user->id,
            'campus' => 'required|string|max:255',
            'academic_program' => 'required|string|max:255',
            'year_level' => 'required|string|max:255',
            'profile_picture' => 'nullable|file|image|max:5120',
            'courses' => 'nullable|array',
            'courses.*' => 'string|max:255',
            'research_interests' => 'nullable|array',
            'research_interests.*' => 'string|max:255',
            'extracurricular_activities' => 'nullable|array',
            'extracurricular_activities.*' => 'string|max:255',
            'academic_goals' => 'nullable|array',
            'academic_goals.*' => 'string|max:255',
            'bio' => 'required|string|min:10|max:500',
            'date_of_birth' => 'required|date|before:today',
            'gender' => 'required|string|in:Male,Female,Lesbian,Gay',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:255',
            'relationship_status' => 'required|string|in:Single,In a Relationship,It\'s Complicated',
            'looking_for' => 'required|string|in:Friendship,Relationship,Casual Date',
            'preferred_gender' => 'nullable|string|in:Male,Female,Lesbian,Gay',
            'preferred_age_min' => 'nullable|integer|min:18|max:100',
            'preferred_age_max' => 'nullable|integer|min:18|max:100|gte:preferred_age_min',
            'preferred_campuses' => 'nullable|array',
            'preferred_campuses.*' => 'string|max:255',
            'ideal_match_qualities' => 'nullable|array',
            'ideal_match_qualities.*' => 'string|max:100',
            'preferred_courses' => 'nullable|array',
            'preferred_courses.*' => 'string|max:255',
        ]);

        \Log::info('Profile setup attempt', ['user_id' => $user->id]);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            $validated['profile_picture'] = $path;
        }

        // Store academic program for suggestions
        if (!empty($validated['academic_program'])) {
            AcademicProgram::incrementOrCreate($validated['academic_program']);
        }

        // Store courses for suggestions
        if (!empty($validated['courses'])) {
            foreach ($validated['courses'] as $course) {
                Course::incrementOrCreate(trim($course));
            }
            // No json_encode needed - User model has array cast
        }

        // Store interests for suggestions
        if (!empty($validated['research_interests'])) {
            foreach ($validated['research_interests'] as $interest) {
                Interest::incrementOrCreate(trim($interest), 'research');
            }
            // No json_encode needed - User model has array cast
        }

        if (!empty($validated['extracurricular_activities'])) {
            foreach ($validated['extracurricular_activities'] as $activity) {
                Interest::incrementOrCreate(trim($activity), 'extracurricular');
            }
            // No json_encode needed - User model has array cast
        }

        if (!empty($validated['academic_goals'])) {
            foreach ($validated['academic_goals'] as $goal) {
                Interest::incrementOrCreate(trim($goal), 'academic_goal');
            }
            // No json_encode needed - User model has array cast
        }

        if (!empty($validated['interests'])) {
            foreach ($validated['interests'] as $interest) {
                Interest::incrementOrCreate(trim($interest), 'hobby');
            }
            // No json_encode needed - User model has array cast
        }

        // Mark profile as completed
        $validated['profile_completed'] = true;

        // Update user profile
        $user->update($validated);

        \Log::info('Profile updated successfully', ['user_id' => $user->id]);

        return redirect()->route('consent.show')->with('success', 'Profile completed! Please accept the terms to continue.');
    }

    /**
     * Update an existing profile
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'display_name' => 'sometimes|string|max:255',
            'fullname' => 'sometimes|string|max:255',
            'campus' => 'sometimes|string|max:255',
            'academic_program' => 'sometimes|string|max:255',
            'year_level' => 'sometimes|string|max:255',
            'profile_picture' => 'nullable|image|max:5120',
            'courses' => 'nullable|string',
            'research_interests' => 'nullable|string',
            'extracurricular_activities' => 'nullable|string',
            'academic_goals' => 'nullable|string',
            'bio' => 'sometimes|string|max:500',
            'date_of_birth' => 'sometimes|date|before:today',
            'gender' => 'sometimes|string|in:Male,Female,Lesbian,Gay',
            'interests' => 'nullable|string',
            'relationship_status' => 'sometimes|string|in:Single,In a Relationship,It\'s Complicated',
            'looking_for' => 'sometimes|string|in:Friendship,Relationship,Casual Date',
            'preferred_gender' => 'nullable|string|in:Male,Female,Lesbian,Gay',
            'preferred_age_min' => 'nullable|integer|min:18|max:100',
            'preferred_age_max' => 'nullable|integer|min:18|max:100|gte:preferred_age_min',
            'preferred_campuses' => 'nullable|array',
            'preferred_campuses.*' => 'string|max:255',
            'ideal_match_qualities' => 'nullable|array',
            'ideal_match_qualities.*' => 'string|max:100',
            'preferred_courses' => 'nullable|array',
            'preferred_courses.*' => 'string|max:255',
        ]);

        $user = Auth::user();

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                Storage::disk('public')->delete($user->profile_picture);
            }

            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            $validated['profile_picture'] = $path;
        }

        $user->update($validated);

        return back()->with('success', 'Profile updated successfully!');
    }
}
