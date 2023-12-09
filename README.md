## MoonShine two-factor authentication

### Requirements

- MoonShine v2.4.0+

### Installation

```shell
composer require moonshine/two-factor
```

```shell
php artisan migrate
```

### Get started

Add pipe to config/moonshine.php

```php
use MoonShine\TwoFactor\TwoFactorAuthPipe;

return [
    // ...
    'auth' => [
        // ...
        'pipelines' => [
            TwoFactorAuthPipe::class
        ],
        // ...
    ]
    // ...
];
```
Add trait TwoFactorAuthenticatable to model or user MoonShine\TwoFactor\Models\MoonshineUser

```php
use MoonShine\TwoFactor\Traits\TwoFactorAuthenticatable;

class MoonshineUser extends Model
{
    use TwoFactorAuthenticatable;
}
```

Add component to ProfilePage

```php
use MoonShine\TwoFactor\ComponentSets\TwoFactor;

protected function components(): array
{
    return [
        // ...

        TwoFactor::make(),
    ];
}
```

