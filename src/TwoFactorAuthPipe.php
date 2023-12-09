<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use MoonShine\MoonShineAuth;

final class TwoFactorAuthPipe
{
    public function handle(Request $request, $next): mixed
    {
        $user = $this->validateCredentials($request);

        if (! is_null($user) && $user->getAttribute('two_factor_secret')) {
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => $request->boolean('remember'),
            ]);

            return redirect()
                ->route('moonshine-two-factor.challenge');
        }

        return $next($request);
    }

    protected function validateCredentials(Request $request): ?Model
    {
        $username = config('moonshine.auth.fields.username');

        /** @var Authenticatable|Model $user $user */
        $user = MoonShineAuth::model()
            ?->query()
            ?->where($username, $request->get('username'))
            ?->first();

        $attempt = MoonShineAuth::provider()
            ?->validateCredentials($user, ['password' => $request->get('password')]);

        if (! $attempt) {
            return null;
        }

        return $user;
    }
}
