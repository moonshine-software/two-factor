<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use JsonException;
use MoonShine\Contracts\Core\DependencyInjection\CrudRequestContract;
use MoonShine\Laravel\Http\Controllers\MoonShineController;
use MoonShine\Laravel\MoonShineAuth;
use MoonShine\Support\Enums\ToastType;
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
    public function check(CrudRequestContract $request): Response|RedirectResponse
    {
        $remember = $request->session()->pull('login.remember', false);
        $id = $request->session()->get('login.id');
        $code = $this->normalizeCode((string) $request->get('code'));
        $recoveryCode = (string) $request->get('recovery_code');

        $model = MoonShineAuth::getModel();

        /** @var Authenticatable|TwoFactorAuthenticatable $user */
        $user = $model
            ?->query()
            ?->find($id);

        if (! $user || ($code === '' && $recoveryCode === '')) {
            return $this->invalidChallengeResponse(
                $request,
                'code',
                __('moonshine-two-factor::validation.invalid_code')
            );
        }

        if ($recoveryCode !== '' && ! $user->verifyByRecoverCode($recoveryCode)) {
            return $this->invalidChallengeResponse(
                $request,
                'recovery_code',
                __('moonshine-two-factor::validation.invalid_recovery_code')
            );
        }

        if ($code !== '' && ! $user->verify($user->two_factor_secret, $code)) {
            return $this->invalidChallengeResponse(
                $request,
                'code',
                __('moonshine-two-factor::validation.invalid_code')
            );
        }

        MoonShineAuth::getGuard()->login($user, $remember);

        $request->session()->forget('login.id');
        $request->session()->regenerate();

        $redirect = $request->session()->pull(
            'url.intended',
            moonshineRouter()->getEndpoints()->home()
        );

        return $request->wantsJson()
            ? $this->json(redirect: $redirect)
            : redirect($redirect);
    }

    /**
     * @throws IncompatibleWithGoogleAuthenticatorException
     * @throws InvalidCharactersException
     * @throws SecretKeyTooShortException
     * @throws JsonException
     */
    public function enable(CrudRequestContract $request): Response
    {
        /** @var Authenticatable|TwoFactorAuthenticatable $user */
        $user = MoonShineAuth::getGuard()->user();

        $user->forceFill([
            'two_factor_secret' => encrypt(app(TwoFactorProvider::class)->generateSecretKey()),
            'two_factor_recovery_codes' => $user->generateRecoveryCode(),
        ])->save();

        return $request->wantsJson()
            ? response()->json(['qr' => $user->twoFactorQrCodeSvg()])
            : back();
    }

    public function disable(CrudRequestContract $request): Response
    {
        /** @var Authenticatable|TwoFactorAuthenticatable $user */
        $user = MoonShineAuth::getGuard()->user();

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
    public function confirm(CrudRequestContract $request): Response
    {
        /** @var Authenticatable|TwoFactorAuthenticatable $user */
        $user = MoonShineAuth::getGuard()->user();
        $code = $this->normalizeCode((string) $request->get('code'));

        if (empty($user->two_factor_secret) ||
            empty($code) ||
            ! $user?->verify($user->two_factor_secret, $code)) {
            return $request->wantsJson()
                ? $this->json(
                    __('moonshine-two-factor::validation.invalid_code'),
                    messageType:ToastType::ERROR
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
    public function refreshCodes(CrudRequestContract $request): Response
    {
        /** @var Authenticatable|TwoFactorAuthenticatable $user */
        $user = MoonShineAuth::getGuard()->user();

        $user->forceFill([
            'two_factor_recovery_codes' => $user->generateRecoveryCode(),
        ])->save();

        return $request->wantsJson()
            ? response()->json($user->recoveryCodes())
            : back();
    }

    private function normalizeCode(string $code): string
    {
        return preg_replace('/\D+/', '', $code) ?? '';
    }

    /**
     * @throws ValidationException
     */
    private function invalidChallengeResponse(
        CrudRequestContract $request,
        string $field,
        string $message,
    ): RedirectResponse {
        if ($request->wantsJson()) {
            throw ValidationException::withMessages([
                $field => [$message],
            ]);
        }

        return redirect()
            ->route('moonshine.moonshine-two-factor.challenge')
            ->withErrors([$field => $message]);
    }
}
