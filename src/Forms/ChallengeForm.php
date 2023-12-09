<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\Forms;

use MoonShine\Components\FormBuilder;
use MoonShine\Fields\Password;

final class ChallengeForm
{
    public static function make(): FormBuilder
    {
        return FormBuilder::make(route('moonshine-two-factor.check'))
            ->fields([
                Password::make(__('moonshine-two-factor::ui.code'), 'code')
                    ->customAttributes(['autocomplete' => 'new-password'])
                    ->eye(),

                Password::make(__('moonshine-two-factor::ui.or_recovery_code'), 'recovery_code')
                    ->customAttributes(['autocomplete' => 'new-password'])
                    ->eye()
            ])
            ->submit(__('moonshine-two-factor::ui.confirm'));
    }
}
