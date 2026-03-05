<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\ComponentSets;

use Closure;
use MoonShine\Crud\Components\Fragment;
use MoonShine\Support\AlpineJs;
use MoonShine\Support\Enums\HttpMethod;
use MoonShine\Support\Enums\JsEvent;
use MoonShine\UI\Components\ActionButton;
use MoonShine\UI\Components\Collapse;
use MoonShine\UI\Components\Components;
use MoonShine\UI\Components\FlexibleRender;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Components\Layout\Box;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\UI\Components\Layout\LineBreak;
use MoonShine\UI\Components\MoonShineComponent;
use MoonShine\UI\Components\When;
use MoonShine\UI\Fields\Password;
use MoonShine\UI\Fields\Text;

final class TwoFactor
{
    public static function make(): MoonShineComponent
    {
        return Components::make([
            LineBreak::make(),
            (new self())->twoFactorBlock()
        ]);
    }

    private function twoFactorWithConfirm(): bool
    {
        return false;
    }

    private function twoFactorEnabled(): bool
    {
        return config('moonshine-two-factor.enable', true);
    }

    private function twoFactorShowSecretCode(): bool
    {
        return config('moonshine-two-factor.show_secret_code', false);
    }

    public function twoFactorBlock(): MoonShineComponent
    {
        return $this->twoFactorEnabled() ? Box::make(__('moonshine-two-factor::ui.2fa'), [
            $this->enableDisableTwoFactor(),

            LineBreak::make(),

            $this->twoFactorQrCodes(),

            LineBreak::make(),

            $this->twoFactorRecoveryCodes(),
        ]) : LineBreak::make();
    }

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
                        route('moonshine.moonshine-two-factor.enable')
                    )
                        ->customAttributes([
                            'style' => $this->twoFactorWithConfirm() ? 'display: none;' : '',
                            'x-on:button-clicked-enable.window' => 'request',
                        ])
                        ->async(HttpMethod::POST, events: [
                            AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'qr-code'),
                            AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'enable-disable'),
                        ])
                ),
                fn () => $this->confirmAction(
                    __('moonshine-two-factor::ui.disable'),
                    ['button-clicked-disable'],
                    fn () => ActionButton::make(
                        __('moonshine-two-factor::ui.disable'),
                        route('moonshine.moonshine-two-factor.disable')
                    )
                        ->customAttributes([
                            'style' => $this->twoFactorWithConfirm() ? 'display: none;' : '',
                            'x-on:button-clicked-disable.window' => 'request',
                        ])
                        ->async(HttpMethod::DELETE, events: [
                            AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'recovery-code'),
                            AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'enable-disable'),
                        ])
                )
            ),
        ])->name('enable-disable')->updateWith(['enable-disable' => is_null(auth()->user()->two_factor_confirmed_at)]);
    }


    protected function twoFactorQrCodes(): MoonShineComponent
    {
        $fields = array_filter([
            FlexibleRender::make(static fn () => auth()->user()?->twoFactorQrCodeSvg()),
            LineBreak::make(),
            $this->twoFactorShowSecretCode()
                ? Text::make(__('moonshine-two-factor::ui.secret_key'), 'secret')
                    ->setValue(auth()->user()?->two_factor_secret
                        ? auth()->user()?->decryptedTwoFactorSecret()
                        : ''
                    )
                    ->readonly()
                    ->copy()
                    ->customAttributes([
                        'spellcheck' => 'false',
                        'style' => 'pointer-events: auto; cursor: pointer',
                        'onclick' => 'this.select(); navigator.clipboard.writeText(this.value)',
                    ])
                : null,
            Text::make(__('moonshine-two-factor::ui.code'), 'code'),
        ]);

        return Fragment::make([
            FlexibleRender::make(static function () use ($fields) {
                if (request('status') === 'qr' && is_null(auth()->user()->two_factor_confirmed_at)) {
                    return FormBuilder::make(route('moonshine.moonshine-two-factor.confirm'))
                        ->async(events: [
                            AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'qr-code'),
                            AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'recovery-code'),
                            AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'enable-disable'),
                        ])
                        ->fields($fields)
                        ->submit(__('moonshine-two-factor::ui.confirm'))
                        ->render();
                }

                return '';
            }),
        ])->name('qr-code')->updateWith(['status' => 'qr']);
    }

    protected function twoFactorRecoveryCodes(): MoonShineComponent
    {
        return Fragment::make([
            When::make(
                static fn () => ! is_null(auth()->user()->two_factor_secret)
                    && ! is_null(auth()->user()->two_factor_confirmed_at),
                fn () => [
                    Collapse::make(__('moonshine-two-factor::ui.show_recovery_code'), [
                        Div::make([
                            FlexibleRender::make(static fn () => collect(
                                auth()->user()->two_factor_secret ? auth()->user()?->recoveryCodes() : []
                            )->implode('<br>')),

                            LineBreak::make(),

                            ...$this->confirmAction(
                                __('moonshine-two-factor::ui.refresh_recovery_codes'),
                                ['button-clicked-refresh-codes'],
                                fn () => ActionButton::make(
                                    __('moonshine-two-factor::ui.refresh_recovery_codes'),
                                    route('moonshine.moonshine-two-factor.refresh-codes')
                                )
                                    ->customAttributes([
                                        'style' => $this->twoFactorWithConfirm() ? 'display: none;' : '',
                                        'x-on:button-clicked-refresh-codes.window' => 'request',
                                    ])
                                    ->async(HttpMethod::POST, events: [
                                        AlpineJs::event(JsEvent::FRAGMENT_UPDATED, 'recovery-code'),
                                    ])
                            ),
                        ])
                    ])->persist(true)->open(false),
                ]
            ),
        ])->name('recovery-code');
    }

    private function confirmAction(string $title, array $events, Closure $action): array
    {
        return array_filter([
            $this->twoFactorWithConfirm() ? ActionButton::make($title, '#')
                ->inModal(
                    __('moonshine-two-factor::ui.confirm'),
                    FormBuilder::make(route('password.confirm'))
                        ->async(events: $events)
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
