<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicProgram;
use App\Models\Course;
use App\Models\Interest;
use Illuminate\Http\Request;

class AutocompleteController extends Controller
{
    /**
     * Get academic program suggestions
     */
    public function academicPrograms(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $suggestions = AcademicProgram::getSuggestions($query, 15);

        return response()->json($suggestions);
    }

    /**
     * Get course suggestions
     */
    public function courses(Request $request)
    {
        $query = $request->input('q', '');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $suggestions = Course::getSuggestions($query, 15);

        return response()->json($suggestions);
    }

    /**
     * Get interest suggestions
     */
    public function interests(Request $request)
    {
        $query = $request->input('q', '');
        $category = $request->input('category');

        if (strlen($query) < 1) {
            return response()->json([]);
        }

        $suggestions = Interest::getSuggestions($query, $category, 15);

        return response()->json($suggestions);
    }
}
