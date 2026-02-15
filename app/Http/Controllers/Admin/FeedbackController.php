<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Feedback', [
            'feedbacks' => Feedback::with('user')->latest()->paginate(15),
        ]);
    }

    public function update(Feedback $feedback)
    {
        $feedback->update(['is_read' => true]);

        return back();
    }
}
