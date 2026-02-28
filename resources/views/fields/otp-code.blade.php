@php
    /** @var \Illuminate\View\ComponentAttributeBag $attributes */
    $fieldErrors = collect($errors ?? [])->filter(static fn ($error): bool => filled($error))->values();
    $hasErrors = $fieldErrors->isNotEmpty();
@endphp

<div
    x-ignore
    x-data="moonshineTwoFactorOtpField()"
    x-init="init()"
    x-on:moonshine-two-factor:toggle-mode.window="toggleMode()"
    class="form-group moonshine-field moonshine-two-factor-otp{{ $hasErrors ? ' moonshine-two-factor-otp--invalid' : '' }}"
    data-field-selector="{{ $column }}"
    data-moonshine-two-factor-otp
>
    @if($label)
        <x-moonshine::form.label for="moonshine-two-factor-digit-0">
            {!! $label !!}
        </x-moonshine::form.label>
    @endif

    <div class="moonshine-two-factor-otp__body">
        <div data-validation-wrapper>
            <input {{ $attributes->merge([
                'type' => 'hidden',
                'id' => 'moonshine-two-factor-code',
                'value' => $value,
                'x-model' => 'value',
            ]) }} />

            <div class="moonshine-two-factor-otp__grid" x-ref="inputs">
                @for($index = 0; $index < 6; $index++)
                    <input
                        type="text"
                        inputmode="numeric"
                        pattern="[0-9]*"
                        maxlength="1"
                        autocomplete="{{ $index === 0 ? 'one-time-code' : 'off' }}"
                        enterkeyhint="{{ $index === 5 ? 'done' : 'next' }}"
                        autocapitalize="off"
                        spellcheck="false"
                        @if($index === 0) autofocus @endif
                        id="moonshine-two-factor-digit-{{ $index }}"
                        data-otp-digit="{{ $index }}"
                        data-validation-field="{{ $column }}"
                        class="form-input moonshine-two-factor-otp__digit"
                        aria-label="{{ $label }} {{ $index + 1 }}"
                        x-on:focus="$el.select()"
                        x-on:input="handleInput($event, {{ $index }})"
                        x-on:keydown="handleKeydown($event, {{ $index }})"
                        x-on:paste="handlePaste($event)"
                    >
                @endfor
            </div>
        </div>

        @foreach($fieldErrors as $error)
            <x-moonshine::form.input-error class="moonshine-two-factor-otp__error">
                {{ $error }}
            </x-moonshine::form.input-error>
        @endforeach

        @if(filled($hint ?? null))
            <x-moonshine::form.hint class="moonshine-two-factor-otp__hint">
                {!! $hint !!}
            </x-moonshine::form.hint>
        @endif
    </div>
</div>
