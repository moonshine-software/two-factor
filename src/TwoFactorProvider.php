<?php

namespace MoonShine\TwoFactor;

use Illuminate\Contracts\Cache\Repository;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use PragmaRX\Google2FA\Google2FA;
use Psr\SimpleCache\InvalidArgumentException;

class TwoFactorProvider
{
    public function __construct(
        protected ?Google2FA $engine = null,
        protected ?Repository $cache = null
    )
    {
        $this->engine = $engine ?? new Google2FA();
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws SecretKeyTooShortException
     * @throws InvalidCharactersException
     */
    public function generateSecretKey(): string
    {
        return $this->engine->generateSecretKey();
    }

    public function qrCodeUrl(string $companyName, string $companyEmail, string $secret): string
    {
        return $this->engine->getQRCodeUrl($companyName, $companyEmail, $secret);
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws InvalidArgumentException
     * @throws SecretKeyTooShortException
     */
    public function verify(string $secret, string $code): bool
    {
        $timestamp = $this->engine->verifyKeyNewer(
            $secret, $code, optional($this->cache)->get($key = 'moonshine.2fa_codes.'.md5($code))
        );

        if ($timestamp !== false) {
            if ($timestamp === true) {
                $timestamp = $this->engine->getTimestamp();
            }

            optional($this->cache)->put($key, $timestamp, ($this->engine->getWindow() ?: 1) * 60);

            return true;
        }

        return false;
    }
}
