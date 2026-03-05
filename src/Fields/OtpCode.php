<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\Fields;

use MoonShine\AssetManager\Css;
use MoonShine\AssetManager\InlineCss;
use MoonShine\AssetManager\InlineJs;
use MoonShine\AssetManager\Js;
use MoonShine\UI\Fields\Field;

final class OtpCode extends Field
{
    protected string $view = 'moonshine-two-factor::fields.otp-code';

    protected bool $hasOld = false;

    protected bool $withWrapper = false;

    protected function resolveValue(): string
    {
        return '';
    }

    protected function viewData(): array
    {
        return [
            'hint' => $this->getHint(),
        ];
    }

    protected function assets(): array
    {
        return [
            ...$this->cssAssets(),
            ...$this->jsAssets(),
        ];
    }

    private function cssAssets(): array
    {
        $publishedCss = public_path('vendor/moonshine-two-factor/otp-code.css');

        if (is_file($publishedCss)) {
            return [
                Css::make('/vendor/moonshine-two-factor/otp-code.css'),
            ];
        }

        $sourceCss = __DIR__ . '/../../resources/css/otp-code.css';

        if (! is_file($sourceCss)) {
            return [];
        }

        return [
            InlineCss::make((string) file_get_contents($sourceCss)),
        ];
    }

    private function jsAssets(): array
    {
        $publishedJs = public_path('vendor/moonshine-two-factor/otp-code.js');

        if (is_file($publishedJs)) {
            return [
                Js::make('/vendor/moonshine-two-factor/otp-code.js')->defer(),
            ];
        }

        $sourceJs = __DIR__ . '/../../resources/js/otp-code.js';

        if (! is_file($sourceJs)) {
            return [];
        }

        return [
            InlineJs::make((string) file_get_contents($sourceJs)),
        ];
    }
}
