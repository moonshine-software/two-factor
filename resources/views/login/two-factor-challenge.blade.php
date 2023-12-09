<div class="authentication">
    <div class="authentication-logo">
        <a href="/" rel="home">
            <img class="h-16"
                 src="{{ asset(config('moonshine.logo') ?? 'vendor/moonshine/logo.svg') }}"
                 alt="{{ config('moonshine.title') }}"
            >
        </a>
    </div>

    <div class="authentication-content">
        <div class="authentication-header">
            <h1 class="title">
                @lang('moonshine-two-factor::ui.2fa')
            </h1>

            <p class="description">
                @lang('moonshine-two-factor::ui.confirm')
            </p>
        </div>

        {!! $form->render() !!}
    </div>
</div>
