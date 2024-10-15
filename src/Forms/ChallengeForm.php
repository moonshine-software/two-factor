<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\Forms;

use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Contracts\UI\FormContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Password;

final class ChallengeForm implements FormContract
{
    public function __invoke(): FormBuilderContract
    {
        return FormBuilder::make(route('moonshine.moonshine-two-factor.check'))
            ->class('authentication-form')
            ->fields([
                Password::make(__('moonshine-two-factor::ui.code'), 'code')
                    ->customAttributes(['autocomplete' => 'off'])
                    ->eye(),

                Password::make(__('moonshine-two-factor::ui.or_recovery_code'), 'recovery_code')
                    ->customAttributes(['autocomplete' => 'off'])
                    ->eye()
            ])
            ->submit(__('moonshine-two-factor::ui.confirm'), ['class' => 'btn btn-primary w-full']);
    }
}
