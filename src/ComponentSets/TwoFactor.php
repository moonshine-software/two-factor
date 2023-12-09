<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\ComponentSets;

use Closure;
use MoonShine\ActionButtons\ActionButton;
use MoonShine\Components\FlexibleRender;
use MoonShine\Components\FormBuilder;
use MoonShine\Components\MoonShineComponent;
use MoonShine\Components\When;
use MoonShine\Decorations\Block;
use MoonShine\Decorations\Fragment;
use MoonShine\Decorations\LineBreak;
use MoonShine\Exceptions\DecorationException;
use MoonShine\Exceptions\PageException;
use MoonShine\Fields\Password;
use MoonShine\Fields\Text;

final class TwoFactor
{
    /**
     * @throws DecorationException
     * @throws PageException
     */
    public static function make(): MoonShineComponent
    {
        return (new self())->twoFactorBlock();
    }

    private function twoFactorWithConfirm(): bool
    {
        return false;
    }

    private function twoFactorEnabled(): bool
    {
        return config('two-factor.enable', true);
    }

    /**
     * @throws DecorationException
     * @throws PageException
     */
    public function twoFactorBlock(): MoonShineComponent
    {
        return $this->twoFactorEnabled() ? Block::make(__('moonshine-two-factor::ui.2fa'), [
            $this->enableDisableTwoFactor(),

            LineBreak::make(),

            $this->twoFactorQrCodes(),

            LineBreak::make(),

            $this->twoFactorRecoveryCodes(),
        ]) : LineBreak::make();
    }

    /**
     * @throws DecorationException
     * @throws PageException
     */
    protected function enableDisableTwoFactor(): MoonShineComponent
    {
        $label = request('enable-disable')
            ? __('moonshine-two-factor::ui.regenerate')
            : __('moonshine-two-factor::ui.enable');

        return Fragment::make([
            When::make(
                static fn () => is_null(auth()->user()->two_factor_confirmed_at),
                fn () => $this->confirmAction(
                    $label,
                    ['button-clicked-enable'],
                    fn () => ActionButton::make(
                        $label,
                        route('moonshine-two-factor.enable')
                    )
                        ->customAttributes([
                            'style' => $this->twoFactorWithConfirm() ? 'display: none;' : '',
                            'x-on:button-clicked-enable.window' => 'request',
                        ])
                        ->async('POST', events: ['fragment-updated-qr-code', 'fragment-updated-enable-disable'])
                ),
                fn () => $this->confirmAction(
                    __('moonshine-two-factor::ui.disable'),
                    ['button-clicked-disable'],
                    fn () => ActionButton::make(
                        __('moonshine-two-factor::ui.disable'),
                        route('moonshine-two-factor.disable')
                    )
                        ->customAttributes([
                            'style' => $this->twoFactorWithConfirm() ? 'display: none;' : '',
                            'x-on:button-clicked-disable.window' => 'request',
                        ])
                        ->async('DELETE', events: [
                            'fragment-updated-qr-code',
                            'fragment-updated-recovery-code',
                            'fragment-updated-enable-disable',
                        ])
                )
            ),
        ])->name('enable-disable')->updateAsync(['enable-disable' => is_null(auth()->user()->two_factor_confirmed_at)]);
    }

    /**
     * @throws DecorationException
     * @throws PageException
     */
    protected function twoFactorQrCodes(): MoonShineComponent
    {
        return Fragment::make([
            FlexibleRender::make(static function () {
                if (request('status') === 'qr' && is_null(auth()->user()->two_factor_confirmed_at)) {
                    return FormBuilder::make(route('moonshine-two-factor.confirm'))
                        ->async(asyncEvents: [
                            'fragment-updated-qr-code',
                            'fragment-updated-recovery-code',
                            'fragment-updated-enable-disable',
                        ])
                        ->fields(
                            array_filter([
                                FlexibleRender::make(static fn () => auth()->user()?->twoFactorQrCodeSvg()),
                                LineBreak::make(),
                                Text::make(__('moonshine-two-factor::ui.code'), 'code'),
                            ])
                        )->submit(__('moonshine-two-factor::ui.confirm'))->render();
                }

                return '';
            }),
        ])->name('qr-code')->updateAsync(['status' => 'qr']);
    }

    /**
     * @throws DecorationException
     * @throws PageException
     */
    protected function twoFactorRecoveryCodes(): MoonShineComponent
    {
        return Fragment::make([
            When::make(
                static fn () => ! is_null(auth()->user()->two_factor_secret)
                    && ! is_null(auth()->user()->two_factor_confirmed_at),
                fn () => [
                    FlexibleRender::make(static fn () => collect(
                        auth()->user()->two_factor_secret ? auth()->user()?->recoveryCodes() : []
                    )->implode('<br>')),

                    LineBreak::make(),

                    ...$this->confirmAction(
                        __('moonshine-two-factor::ui.refresh_recovery_codes'),
                        ['button-clicked-refresh-codes'],
                        fn () => ActionButton::make(
                            __('moonshine-two-factor::ui.refresh_recovery_codes'),
                            route('moonshine-two-factor.refresh-codes')
                        )
                            ->customAttributes([
                                'style' => $this->twoFactorWithConfirm() ? 'display: none;' : '',
                                'x-on:button-clicked-refresh-codes.window' => 'request',
                            ])
                            ->async('POST', events: ['fragment-updated-recovery-code'])
                    ),
                ]
            ),
        ])->name('recovery-code')->updateAsync();
    }

    private function confirmAction(string $title, array $events, Closure $action): array
    {
        return array_filter([
            $this->twoFactorWithConfirm() ? ActionButton::make($title, '#')
                ->inModal(
                    __('moonshine-two-factor::ui.confirm'),
                    FormBuilder::make(route('password.confirm'))
                        ->async(asyncEvents: $events)
                        ->fields([
                            Password::make(trans('moonshine::ui.resource.password'), 'password')
                                ->customAttributes(['autocomplete' => 'new-password'])
                                ->eye(),
                        ])
                        ->submit(__('moonshine-two-factor::ui.confirm')),
                ) : null,
            $action(),
        ]);
    }
}
