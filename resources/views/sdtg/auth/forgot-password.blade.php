<x-guest-layout>
    <div id="authLoginWrap">
        <div class="auth-welcome">
            <p class="auth-welcome__label">{{ config('identity.admin.login_secondary_label', 'Send Down Thy Glory') }}</p>
            <h2 class="auth-welcome__title">Forgot Password</h2>
            <p class="auth-welcome__lead">
                Enter the email for your SDTG admin account and we will send a secure reset link.
            </p>
        </div>

        <form id="authForm" class="auth-form" method="POST" action="{{ route('sdtg.password.email') }}">
            @csrf

            @if (session('status'))
                <div class="auth-form-notice" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div id="authFormError" class="auth-form-error" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="auth-field @error('email') is-error @enderror">
                <input
                    type="email"
                    id="authEmail"
                    name="email"
                    class="auth-field__input"
                    placeholder=" "
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                    autofocus
                >
                <label class="auth-field__label" for="authEmail">Email Address</label>
                <p class="auth-field__error" role="alert">Please enter a valid email address.</p>
            </div>

            <button type="submit" class="auth-submit">
                <span class="auth-submit__content">
                    <i class="fas fa-paper-plane auth-submit__text" aria-hidden="true"></i>
                    <span class="auth-submit__text">Send Reset Link</span>
                </span>
            </button>

            <p class="auth-form__footer">
                <a class="auth-link" href="{{ route('sdtg.login') }}">Back to SDTG login</a>
            </p>
        </form>
    </div>
</x-guest-layout>
