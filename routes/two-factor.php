<?php

use Illuminate\Support\Facades\Route;
use MoonShine\Pages\ViewPage;
use MoonShine\TwoFactor\Forms\ChallengeForm;
use MoonShine\TwoFactor\Http\Controllers\TwoFactorController;

Route::prefix(config('moonshine.route.prefix', ''))
    ->middleware('moonshine')
    ->as('moonshine-two-factor.')->group(static function (): void {
        Route::middleware(config('moonshine.auth.middleware', []))
            ->prefix('two-factor')
            ->controller(TwoFactorController::class)
            ->group(function (): void {

            Route::post(
                'enable',
                'enable',
            )->name('enable');

            Route::delete(
                'disable',
                'disable',
            )->name('disable');

            Route::post(
                'confirm',
                'confirm',
            )->name('confirm');

            Route::post(
                'refresh-codes',
                'refreshCodes',
            )->name('refresh-codes');

            Route::post(
                'check',
                'check',
            )->name('check')
                ->withoutMiddleware(config('moonshine.auth.middleware', []));

            Route::get('challenge', static function () {
                return ViewPage::make()
                    ->setLayout('moonshine::layouts.login')
                    ->setContentView(
                        'moonshine-two-factor::login.two-factor-challenge',
                        ['form' => ChallengeForm::make()]
                    );
            })->name('challenge')
                ->withoutMiddleware(config('moonshine.auth.middleware', []));
        });
    });
