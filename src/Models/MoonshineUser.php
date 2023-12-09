<?php

declare(strict_types=1);

namespace MoonShine\TwoFactor\Models;

use MoonShine\Models\MoonshineUser as User;
use MoonShine\TwoFactor\Traits\TwoFactorAuthenticatable;

class MoonshineUser extends User
{
    use TwoFactorAuthenticatable;
}
