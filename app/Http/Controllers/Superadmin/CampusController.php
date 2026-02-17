<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Campus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CampusController extends Controller
{
    /**
     * List campuses for Find Your Match base locations.
     */
    public function index(): Response
    {
        $campuses = Campus::orderBy('name')->get([
            'id', 'name', 'code', 'base_latitude', 'base_longitude', 'created_at',
        ]);

        return Inertia::render('Superadmin/Campuses', [
            'campuses' => $campuses,
        ]);
    }

    /**
     * Show create form (optional; can be modal on index).
     */
    public function create(): Response
    {
        return Inertia::render('Superadmin/CampusForm', [
            'campus' => null,
        ]);
    }

    /**
     * Store a new campus.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:64|unique:campuses,code',
            'base_latitude' => 'nullable|numeric|between:-90,90',
            'base_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        Campus::create($validated);
        return redirect()->route('superadmin.campuses.index')
            ->with('success', 'Campus created.');
    }

    /**
     * Show edit form.
     */
    public function edit(Campus $campus): Response
    {
        return Inertia::render('Superadmin/CampusForm', [
            'campus' => [
                'id' => $campus->id,
                'name' => $campus->name,
                'code' => $campus->code,
                'base_latitude' => $campus->base_latitude,
                'base_longitude' => $campus->base_longitude,
            ],
        ]);
    }

    /**
     * Update campus.
     */
    public function update(Request $request, Campus $campus): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:64|unique:campuses,code,' . $campus->id,
            'base_latitude' => 'nullable|numeric|between:-90,90',
            'base_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $campus->update($validated);
        return redirect()->route('superadmin.campuses.index')
            ->with('success', 'Campus updated.');
    }

    /**
     * Delete campus.
     */
    public function destroy(Campus $campus): RedirectResponse
    {
        $campus->delete();
        return redirect()->route('superadmin.campuses.index')
            ->with('success', 'Campus deleted.');
    }
}
