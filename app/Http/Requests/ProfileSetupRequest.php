<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileSetupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => 'required|string|max:255',
            'fullname' => 'required|string|max:255',
            'campus' => 'required|string|max:255',
            'academic_program' => 'required|string|max:255',
            'year_level' => 'required|string|max:255',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'courses' => 'nullable|string|max:1000',
            'research_interests' => 'nullable|string|max:1000',
            'extracurricular_activities' => 'nullable|string|max:1000',
            'academic_goals' => 'nullable|string|max:1000',
            'bio' => 'required|string|min:20|max:500',
            'date_of_birth' => 'required|date|before:today|after:1950-01-01',
            'gender' => 'required|string|in:Male,Female,Non-binary,Prefer not to say',
            'interests' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'display_name.required' => 'Please enter a display name.',
            'fullname.required' => 'Please enter your full name.',
            'campus.required' => 'Please select your campus.',
            'academic_program.required' => 'Please enter your academic program.',
            'year_level.required' => 'Please select your year level.',
            'bio.required' => 'Please write a short bio about yourself.',
            'bio.min' => 'Your bio must be at least 20 characters.',
            'bio.max' => 'Your bio cannot exceed 500 characters.',
            'date_of_birth.required' => 'Please enter your date of birth.',
            'date_of_birth.before' => 'Please enter a valid date of birth.',
            'gender.required' => 'Please select your gender.',
            'profile_picture.image' => 'The file must be an image.',
            'profile_picture.max' => 'The image size cannot exceed 5MB.',
        ];
    }
}
