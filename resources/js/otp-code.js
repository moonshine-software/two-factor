function initOtpFields() {
    document.querySelectorAll('[data-moonshine-two-factor-otp]').forEach(el => {
        el.removeAttribute('x-ignore');
        delete el._x_ignore;
        delete el._x_ignoreSelf;

        if (!el._x_dataStack) Alpine.initTree(el);
    });
}

function registerOtpComponent() {
    if (window.moonshineTwoFactorOtpFieldRegistered) {
        return initOtpFields();
    }

    window.moonshineTwoFactorOtpFieldRegistered = true;

    Alpine.data('moonshineTwoFactorOtpField', () => ({
        value: '',
        submitting: false,
        _observer: null,
        _digitEls: [],

        init() {
            this._digitEls = [...this.$refs.inputs.querySelectorAll('[data-otp-digit]')];

            this._bindRecoveryInput();
            this._watchErrors();

            this.$nextTick(() => {
                this._focus(0);

                if (this._recoveryWrapper()?.querySelector('.form-error')) {
                    this._setMode(true);
                }
            });
        },

        destroy() {
            this._observer?.disconnect();
        },

        _recoveryWrapper() {
            return document.querySelector('[data-field-selector="recovery_code"]');
        },

        _recoveryInput() {
            return document.querySelector('input[name="recovery_code"]');
        },

        _focus(i) {
            const el = this._digitEls[Math.max(0, Math.min(i, this._digitEls.length - 1))];
            el?.focus();
            el?.select();
        },

        _sync() {
            this.value = this._digitEls.map(el => el.value).join('');
        },

        _clearDigits() {
            this._digitEls.forEach(el => el.value = '');
            this._sync();
        },

        _clearErrors() {
            this.$root.classList.remove('moonshine-two-factor-otp--invalid');
            this._digitEls.forEach(el => el.classList.remove('form-invalid'));
            this.$root.querySelector('input[name="code"]')?.classList.remove('form-invalid');
            this.$root.querySelectorAll('.form-error, .moonshine-two-factor-otp__error').forEach(el => el.remove());
        },

        _clearRecoveryErrors() {
            const input = this._recoveryInput();
            input?.classList.remove('form-invalid');
            this._recoveryWrapper()?.querySelectorAll('.form-error').forEach(el => el.remove());

            const next = input?.closest('[data-validation-wrapper]')?.nextElementSibling;
            if (next?.classList.contains('form-error')) next.remove();
        },

        _applyCode(raw) {
            const clean = raw.replace(/\D/g, '').slice(0, this._digitEls.length);
            if (clean) this._clearErrors();

            this._digitEls.forEach((el, i) => el.value = clean[i] ?? '');
            this._sync();
            this._focus(clean.length >= this._digitEls.length ? this._digitEls.length - 1 : clean.length);
            this._trySubmit();
        },

        _trySubmit() {
            if (this.submitting || this.value.length !== this._digitEls.length) return;

            const form = this.$root.closest('form');
            if (!(form instanceof HTMLFormElement)) return;

            this.submitting = true;
            this.$nextTick(() => form.requestSubmit());
        },

        handleInput(event, index) {
            const clean = event.target.value.replace(/\D/g, '');

            if (clean.length > 1) return this._applyCode(clean);

            if (clean) this._clearErrors();
            event.target.value = clean;
            this._sync();

            if (clean && index < this._digitEls.length - 1) this._focus(index + 1);

            this._trySubmit();
        },

        handleKeydown(event, index) {
            switch (event.key) {
                case 'Backspace':
                    if (!this._digitEls[index]?.value && index > 0) {
                        this._digitEls[index - 1].value = '';
                        this._sync();
                        this._focus(index - 1);
                        event.preventDefault();
                    }
                    break;
                case 'ArrowLeft':
                    if (index > 0) { this._focus(index - 1); event.preventDefault(); }
                    break;
                case 'ArrowRight':
                    if (index < this._digitEls.length - 1) { this._focus(index + 1); event.preventDefault(); }
                    break;
            }
        },

        handlePaste(event) {
            const text = event.clipboardData?.getData('text') ?? '';
            if (!text) return;
            event.preventDefault();
            this._applyCode(text);
        },

        toggleMode() {
            const rw = this._recoveryWrapper();
            this._setMode(!rw || rw.hidden);
        },

        _setMode(recovery) {
            const rw = this._recoveryWrapper();
            if (!rw) return;

            const cw = this.$root.closest('[data-field-selector="code"]');
            if (cw) cw.hidden = recovery;
            rw.hidden = !recovery;

            window.dispatchEvent(new CustomEvent('moonshine-two-factor:mode-changed', {
                detail: { isRecoveryMode: recovery },
            }));

            this.submitting = false;

            if (recovery) {
                this._clearDigits();
                this._recoveryInput()?.focus();
            } else {
                const ri = this._recoveryInput();
                if (ri) ri.value = '';
                this.$nextTick(() => this._focus(0));
            }
        },

        _bindRecoveryInput() {
            const input = this._recoveryInput();
            if (!input || input.dataset.twoFactorBound) return;

            input.dataset.twoFactorBound = 'true';
            input.addEventListener('input', () => {
                if (input.value) this._clearRecoveryErrors();
            });
        },

        _watchErrors() {
            const form = this.$root.closest('form');
            if (!form || this._observer) return;

            const valWrapper = this.$root.querySelector('[data-validation-wrapper]');

            this._observer = new MutationObserver(mutations => {
                const isCodeError = mutations.some(m =>
                    (m.type === 'attributes' && m.target.matches?.('[data-otp-digit]') && m.target.classList.contains('form-invalid'))
                    || [...m.addedNodes].some(n => n.classList?.contains('form-error') && n.previousElementSibling === valWrapper)
                );

                if (isCodeError) {
                    this.submitting = false;
                    this._clearDigits();
                    this.$nextTick(() => this._focus(0));
                    return;
                }

                const ri = this._recoveryInput();
                const rw = this._recoveryWrapper();
                const rvw = ri?.closest('[data-validation-wrapper]');

                const isRecoveryError = mutations.some(m =>
                    (m.type === 'attributes' && m.target === ri && m.target.classList.contains('form-invalid'))
                    || [...m.addedNodes].some(n =>
                        n.classList?.contains('form-error') && (rw?.contains(n) || n.previousElementSibling === rvw)
                    )
                );

                if (isRecoveryError) {
                    this.submitting = false;
                    if (ri) { ri.value = ''; ri.focus(); }
                }
            });

            this._observer.observe(form, {
                subtree: true,
                childList: true,
                attributes: true,
                attributeFilter: ['class'],
            });
        },
    }));

    initOtpFields();
}

if (typeof window.Alpine !== 'undefined') {
    registerOtpComponent();
} else {
    document.addEventListener('alpine:init', registerOtpComponent, { once: true });
}

