<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use JsonException;
use MoonShine\Http\Controllers\MoonShineController;
use MoonShine\MoonShineAuth;
use MoonShine\MoonShineRequest;
use MoonShine\TwoFactor\Traits\TwoFactorAuthenticatable;
use MoonShine\TwoFactor\TwoFactorProvider;
use PragmaRX\Google2FA\Exceptions\IncompatibleWithGoogleAuthenticatorException;
use PragmaRX\Google2FA\Exceptions\InvalidCharactersException;
use PragmaRX\Google2FA\Exceptions\SecretKeyTooShortException;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorController extends MoonShineController
{
    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws InvalidArgumentException
     * @throws SecretKeyTooShortException
     * @throws JsonException
     */
    public function check(MoonShineRequest $request): RedirectResponse
    {
        $remember = $request->session()->pull('login.remember', false);
        $id = $request->session()->get('login.id');

        $model = MoonShineAuth::model();

        /** @var Authenticatable|TwoFactorAuthenticatable $user */
        $user = $model
            ?->query()
            ?->find($id);

        if (! $user || ! $request->anyFilled(['recovery_code', 'code'])) {
            return redirect()
                ->route('moonshine-two-factor.challenge')
                ->withErrors(['code' => __('moonshine-two-factor::validation.invalid_code')]);
        }

        if ($request->filled('recovery_code') && ! $user->verifyByRecoverCode(request('recovery_code'))) {
            return redirect()
                ->route('moonshine-two-factor.challenge')
                ->withErrors(['recovery_code' => __('moonshine-two-factor::validation.invalid_recovery_code')]);
        }

        if ($request->filled('code') && ! $user->verify($user->two_factor_secret, $request->code)) {
            return redirect()
                ->route('moonshine-two-factor.challenge')
                ->withErrors(['code' => __('moonshine-two-factor::validation.invalid_code')]);
        }

        MoonShineAuth::guard()->login($user, $remember);

        $request->session()->forget('login.id');
        $request->session()->regenerate();

        return redirect()->intended(
            route(
                moonshineIndexRoute()
            )
        );
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws JsonException
     */
    public function enable(MoonShineRequest $request): Response
    {
        /** @var Authenticatable|TwoFactorAuthenticatable $user */
        $user = MoonShineAuth::guard()->user();

        $user->forceFill([
            'two_factor_secret' => encrypt(app(TwoFactorProvider::class)->generateSecretKey()),
            'two_factor_recovery_codes' => $user->generateRecoveryCode(),
        ])->save();

        return $request->wantsJson()
            ? response()->json(['qr' => $user->twoFactorQrCodeSvg()])
            : back();
    }

    public function disable(MoonShineRequest $request): Response
    {
        /** @var Authenticatable|TwoFactorAuthenticatable $user */
        $user = MoonShineAuth::guard()->user();

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $request->wantsJson()
            ? response()->noContent()
            : back();
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws InvalidArgumentException
     */
    public function confirm(MoonShineRequest $request): Response
    {
        /** @var Authenticatable|TwoFactorAuthenticatable $user */
        $user = MoonShineAuth::guard()->user();
        $code = $request->get('code');

        if (empty($user->two_factor_secret) ||
            empty($code) ||
            ! $user?->verify($user->two_factor_secret, $code)) {
            return $request->wantsJson()
                ? $this->json(
                    __('moonshine-two-factor::validation.invalid_code'),
                    messageType: 'error'
                )
                : back()
                    ->withErrors(['code' => __('moonshine-two-factor::validation.invalid_code')]);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $request->wantsJson()
            ? response()->noContent()
            : back();
    }

    /**
     * @throws JsonException
     */
    public function refreshCodes(MoonShineRequest $request): Response
    {
        /** @var Authenticatable|TwoFactorAuthenticatable $user */
        $user = MoonShineAuth::guard()->user();

        $user->forceFill([
            'two_factor_recovery_codes' => $user->generateRecoveryCode(),
        ])->save();

        return $request->wantsJson()
            ? response()->json($user->recoveryCodes())
            : back();
    }
}
