<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Vite;
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
        $this->configureDefaults();
        $this->configureVitePreload();
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
}
