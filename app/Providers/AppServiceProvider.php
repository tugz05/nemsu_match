<?php

namespace App\Providers;

use App\Models\Superadmin\AppSetting;
use Carbon\CarbonImmutable;
use App\Events\NotificationSent;
use App\Listeners\SendWebPushForNotification;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(NotificationSent::class, SendWebPushForNotification::class);

        $this->configureDefaults();
        $this->configureVitePreload();
        $this->shareBrandingWithViews();

        // When APP_URL is HTTPS (e.g. ngrok), force all generated URLs to HTTPS to avoid mixed content
        $appUrl = config('app.url');
        if ($appUrl && str_starts_with(strtolower($appUrl), 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Disable preload for CSS to avoid browser warnings:
     * "The resource ... was preloaded using link preload but not used within a few seconds."
     * Stylesheets are still loaded via normal <link rel="stylesheet"> tags.
     */
    protected function configureVitePreload(): void
    {
        Vite::usePreloadTagAttributes(function (?string $src, ?string $url): array|false {
            if ($url !== null && preg_match('/\.css(\?.*)?$/i', $url) === 1) {
                return false;
            }
            return [];
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * Share branding (app logo, header icon) with all views for favicon and layout use.
     */
    protected function shareBrandingWithViews(): void
    {
        $logoPath = AppSetting::get('app_logo', '');
        $headerIconPath = AppSetting::get('header_icon', '');

        View::share('branding', [
            'app_logo_url' => $logoPath ? asset('storage/' . ltrim($logoPath, '/')) : null,
            'header_icon_url' => $headerIconPath ? asset('storage/' . ltrim($headerIconPath, '/')) : null,
        ]);
    }
}
