<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\Forms;

use MoonShine\Contracts\UI\FormBuilderContract;
use MoonShine\Contracts\UI\FormContract;
use MoonShine\UI\Components\FormBuilder;
use MoonShine\UI\Fields\Password;
use MoonShine\TwoFactor\Fields\OtpCode;

final class ChallengeForm implements FormContract
{
    public function __invoke(): FormBuilderContract
    {
        return FormBuilder::make(route('moonshine.moonshine-two-factor.check'))
            ->class('authentication-form')
            ->async()
            ->withoutErrorToast()
            ->errorsAbove(false)
            ->fields([
                OtpCode::make(__('moonshine-two-factor::ui.code'), 'code'),

                Password::make(__('moonshine-two-factor::ui.or_recovery_code'), 'recovery_code')
                    ->customAttributes([
                        'autocomplete' => 'off',
                        'autocapitalize' => 'off',
                        'spellcheck' => 'false',
                    ])
                    ->customWrapperAttributes([
                        'hidden' => 'hidden',
                    ])
                    ->eye()
            ])
            ->submit(__('moonshine-two-factor::ui.confirm'), ['class' => 'btn btn-primary w-full']);
    }
}
