<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor;

use Illuminate\Support\ServiceProvider;
use MoonShine\Laravel\Pages\ProfilePage;
use MoonShine\TwoFactor\ComponentSets\TwoFactor;

final class TwoFactorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/moonshine-two-factor.php', 'moonshine-two-factor');
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/moonshine-two-factor.php');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'moonshine-two-factor');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'moonshine-two-factor');

        if (method_exists($this, 'publishesMigrations')) {
            $this->publishesMigrations([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ]);
        } else {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/moonshine-two-factor'),
        ]);

        $this->publishes([
            __DIR__ . '/../config/moonshine-two-factor.php' => config_path('moonshine-two-factor.php'),
        ]);

        $this->publishes([
            __DIR__ . '/../resources/css/otp-code.css' => public_path('vendor/moonshine-two-factor/otp-code.css'),
            __DIR__ . '/../resources/js/otp-code.js' => public_path('vendor/moonshine-two-factor/otp-code.js'),
        ], 'moonshine-two-factor-assets');

        app()->singleton(TwoFactorProvider::class);

        $profile = config('moonshine.pages.profile', ProfilePage::class);
        $profile::pushComponent(fn() => TwoFactor::make());
    }
}
