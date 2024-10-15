<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\Pages;

use MoonShine\Contracts\UI\LayoutContract;
use MoonShine\Laravel\Layouts\LoginLayout;
use MoonShine\Laravel\Pages\Page;
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
        return $layout->title(
            __('moonshine-two-factor::ui.2fa')
        )->description(
            __('moonshine-two-factor::ui.confirm')
        );
    }
}
