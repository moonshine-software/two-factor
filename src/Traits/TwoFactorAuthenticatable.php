<?php

namespace MoonShine\TwoFactor\Traits;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use JsonException;
use MoonShine\TwoFactor\TwoFactorProvider;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * @mixin Model
 */
trait TwoFactorAuthenticatable
{
    public function hasEnabledTwoFactorAuthentication(): bool
    {
        if (config('two-factor.enable', true)) {
            return ! is_null($this->two_factor_secret) &&
                ! is_null($this->two_factor_confirmed_at);
        }

        return ! is_null($this->two_factor_secret);
    }

    /**
     * @throws JsonException
     */
    public function recoveryCodes(): array
    {
        return json_decode(
            decrypt($this->two_factor_recovery_codes),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
    }

    public function replaceRecoveryCode($code): void
    {
        $this->forceFill([
            'two_factor_recovery_codes' => encrypt(str_replace(
                $code,
                Str::random(10).'-'.Str::random(10),
                decrypt($this->two_factor_recovery_codes)
            )),
        ])->save();
    }

    /**
     * @throws JsonException
     */
    public function generateRecoveryCode(): string
    {
        return encrypt(
            json_encode(
                Collection::times(8, static function () {
                    return Str::random(10) . '-' . Str::random(10);
                })->all(),
                JSON_THROW_ON_ERROR
            )
        );
    }

    public function twoFactorQrCodeSvg(): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd
            )
        ))->writeString($this->twoFactorQrCodeUrl());

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws InvalidArgumentException
     */
    public function verify(string $secret, string $code): bool
    {
        return app(TwoFactorProvider::class)->verify(
            decrypt($secret),
            $code
        );
    }

    /**
     * @throws JsonException
     */
    public function verifyByRecoverCode(string $code): bool
    {
        $code = collect($this->recoveryCodes())
            ->first(fn ($c) => hash_equals($c, $code));

        if (! $code) {
            return false;
        }

        $this->replaceRecoveryCode($code);

        return true;
    }

    public function twoFactorQrCodeUrl(): string
    {
        return app(TwoFactorProvider::class)->qrCodeUrl(
            config('app.name'),
            $this->{config('moonshine.auth.fields.username', 'email')},
            decrypt($this->two_factor_secret)
        );
    }
}
