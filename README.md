## MoonShine two-factor authentication

### Requirements

- MoonShine v4.0+

### Support MoonShine versions

| MoonShine | Layouts |
|-----------|---------|
| 2.0+      | 1.0+    |
| 3.0+      | 2.0+    |
| 4.0+      | 3.0+    |

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

or in `MoonShineServiceProvider`

```php
use MoonShine\TwoFactor\TwoFactorAuthPipe;

$config->authPipelines([
    TwoFactorAuthPipe::class
]);
```

Add trait TwoFactorAuthenticatable to model or use MoonShine\TwoFactor\Models\MoonshineUser

```php
use MoonShine\TwoFactor\Traits\TwoFactorAuthenticatable;

class MoonshineUser extends Model
{
    use TwoFactorAuthenticatable;
}
```

We will automatically add the component to the profile page, but if you use another page, you can add it yourself.

```php
use MoonShine\TwoFactor\ComponentSets\TwoFactor;

protected function components(): iterable
{
    return [
        // ...

        TwoFactor::make(),
    ];
}
```

