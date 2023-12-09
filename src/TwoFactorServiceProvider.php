<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor;

use Illuminate\Support\ServiceProvider;

final class TwoFactorServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/two-factor.php');
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'moonshine-two-factor');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'moonshine-two-factor');

        $this->publishes([
            __DIR__ . '/../lang' => $this->app->langPath('vendor/moonshine-two-factor'),
        ]);

        $this->publishes([
            __DIR__.'/../config/two-factor.php' => config_path('two-factor.php'),
        ]);

        app()->singleton(TwoFactorProvider::class);
    }
}
