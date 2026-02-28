<?php

use Illuminate\Support\Facades\Route;
use MoonShine\TwoFactor\Http\Controllers\TwoFactorController;
use MoonShine\TwoFactor\Pages\ChallengePage;

Route::moonshine(static function (): void {
        Route::as('moonshine-two-factor.')
            ->prefix('t/f/two-factor')
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
                ->withoutMiddleware(moonshineConfig()->getAuthMiddleware());

            Route::get('challenge', static function (ChallengePage $page) {
                return $page;
            })->name('challenge')
                ->withoutMiddleware(moonshineConfig()->getAuthMiddleware());
        });
}, withAuthenticate: true);
