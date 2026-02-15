<?php

namespace App\Http\Middleware;

use App\Models\Superadmin\AppSetting;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $logoPath = AppSetting::get('app_logo', '');
        $headerIconPath = AppSetting::get('header_icon', '');
        $user = $request->user();
        $freemiumEnabled = $user && \App\Models\User::freemiumEnabled();
        $isPlus = $user && $user->isPlus();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'branding' => [
                'app_logo_url' => $logoPath ? asset('storage/'.ltrim($logoPath, '/')) : null,
                'header_icon_url' => $headerIconPath ? asset('storage/'.ltrim($headerIconPath, '/')) : null,
            ],
            'subscription' => [
                'freemium_enabled' => $freemiumEnabled,
                'is_plus' => $isPlus,
            ],
        ];
    }
}
