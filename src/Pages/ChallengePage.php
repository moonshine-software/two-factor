<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\Pages;

use MoonShine\Contracts\UI\LayoutContract;
use MoonShine\Laravel\Layouts\LoginLayout;
use MoonShine\Laravel\Pages\Page;
use Illuminate\Support\Js;
use MoonShine\UI\Components\Link;
use MoonShine\UI\Components\Layout\Div;
use MoonShine\TwoFactor\Forms\ChallengeForm;

final class ChallengePage extends Page
{
    protected ?string $layout = LoginLayout::class;

    protected function components(): iterable
    {
        return [
            (new ChallengeForm)()
        ];
    }

    /**
     * @param  LoginLayout  $layout
     */
    protected function modifyLayout(LayoutContract $layout): LayoutContract
    {
        if ($layout instanceof LoginLayout) {
            $layout::pushComponent(
                Div::make([
                    Link::make('#', __('moonshine-two-factor::ui.use_recovery_code'))
                        ->customAttributes([
                            'data-moonshine-two-factor-recovery-link' => true,
                            'x-data' => sprintf(
                                "{ recoveryLabel: %s, authenticatorLabel: %s, isRecoveryMode: false }",
                                Js::from(__('moonshine-two-factor::ui.use_recovery_code')),
                                Js::from(__('moonshine-two-factor::ui.use_authenticator_code')),
                            ),
                            'x-on:moonshine-two-factor:mode-changed.window' => 'isRecoveryMode = !!($event.detail && $event.detail.isRecoveryMode)',
                            'x-bind:aria-expanded' => "isRecoveryMode ? 'true' : 'false'",
                            'x-on:click.prevent' => '$dispatch(\'moonshine-two-factor:toggle-mode\')',
                            'x-text' => 'isRecoveryMode ? authenticatorLabel : recoveryLabel',
                        ]),
                ])->class('authentication-recovery-link text-center mt-4 text-sm')
            );
        }

        return $layout->title(
            __('moonshine-two-factor::ui.2fa')
        )->description(
            __('moonshine-two-factor::ui.code_hint')
        );
    }
}
