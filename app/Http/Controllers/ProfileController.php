<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show a user's profile. Redirect to account if viewing own profile.
     */
    public function show(User $user): Response|\Illuminate\Http\RedirectResponse
    {
        $currentUser = Auth::user();

        if ((int) $user->id === (int) $currentUser->id) {
            return redirect()->route('account');
        }

        $row = DB::table('users')->where('id', $user->id)->first();
        if (! $row) {
            return redirect()->route('home.feed');
        }

        $decode = function ($raw) {
            if ($raw === null || $raw === '') {
                return [];
            }
            if (is_array($raw)) {
                return array_values(array_filter($raw, 'is_string'));
            }
            if (! is_string($raw)) {
                return [];
            }
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter($decoded, 'is_string'));
            }
            if (is_string($decoded ?? null)) {
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
        $preferredCampuses = $decode($row->preferred_campuses ?? null);
        $idealMatchQualities = $decode($row->ideal_match_qualities ?? null);
        $preferredCourses = $decode($row->preferred_courses ?? null);

        $isFollowedByUser = $currentUser->isFollowing($user);
        $followingCount = $user->following()->count();
        $followersCount = $user->followers()->count();
        $postsCount = Post::where('user_id', $user->id)->count();
        $memberSince = $user->created_at ? $user->created_at->diffForHumans(null, true) : null;

        return Inertia::render('Profile', [
            'profileUser' => [
                'id' => $user->id,
                'display_name' => $user->display_name,
                'fullname' => $user->fullname,
                'is_workspace_verified' => (bool) $user->is_workspace_verified,
                'campus' => $user->campus,
                'academic_program' => $user->academic_program,
                'year_level' => $user->year_level,
                'profile_picture' => $user->profile_picture,
                'bio' => $user->bio,
                'date_of_birth' => $user->date_of_birth,
                'gender' => $user->gender,
                'relationship_status' => $user->relationship_status,
                'looking_for' => $user->looking_for,
                'preferred_gender' => $user->preferred_gender,
                'preferred_age_min' => $user->preferred_age_min,
                'preferred_age_max' => $user->preferred_age_max,
                'courses' => $courses,
                'research_interests' => $researchInterests,
                'extracurricular_activities' => $extracurricularActivities,
                'academic_goals' => $academicGoals,
                'interests' => $interests,
                'preferred_campuses' => $preferredCampuses,
                'ideal_match_qualities' => $idealMatchQualities,
                'preferred_courses' => $preferredCourses,
                'following_count' => $followingCount,
                'followers_count' => $followersCount,
                'posts_count' => $postsCount,
                'member_since' => $memberSince,
            ],
            'is_followed_by_user' => $isFollowedByUser,
        ]);
    }
}
