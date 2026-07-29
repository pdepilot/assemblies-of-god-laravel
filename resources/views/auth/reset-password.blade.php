<x-guest-layout>
    <div id="authLoginWrap">
        <div class="auth-welcome">
            <p class="auth-welcome__label">Administrator Portal</p>
            <h2 class="auth-welcome__title">Reset Password</h2>
            <p class="auth-welcome__lead">
                Choose a new password for your administrator account.
            </p>
        </div>

        <form id="authForm" class="auth-form" method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

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
                    value="{{ old('email', $email) }}"
                    autocomplete="username"
                    required
                    autofocus
                >
                <label class="auth-field__label" for="authEmail">Email Address</label>
                <p class="auth-field__error" role="alert">Please enter a valid email address.</p>
            </div>

            <div class="auth-field @error('password') is-error @enderror">
                <input
                    type="password"
                    id="authPassword"
                    name="password"
                    class="auth-field__input"
                    placeholder=" "
                    autocomplete="new-password"
                    required
                    minlength="8"
                >
                <label class="auth-field__label" for="authPassword">New Password</label>
                <button type="button" id="authPasswordToggle" class="auth-field__toggle" aria-label="Show password">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
                <p class="auth-field__error" role="alert">Password must be at least 8 characters.</p>
            </div>

            <div class="auth-field @error('password_confirmation') is-error @enderror">
                <input
                    type="password"
                    id="authPasswordConfirm"
                    name="password_confirmation"
                    class="auth-field__input"
                    placeholder=" "
                    autocomplete="new-password"
                    required
                    minlength="8"
                >
                <label class="auth-field__label" for="authPasswordConfirm">Confirm Password</label>
                <p class="auth-field__error" role="alert">Please confirm your password.</p>
            </div>

            <button type="submit" class="auth-submit">
                <span class="auth-submit__content">
                    <i class="fas fa-key auth-submit__text" aria-hidden="true"></i>
                    <span class="auth-submit__text">Reset Password</span>
                </span>
            </button>

            <p class="auth-form__footer">
                <a class="auth-link" href="{{ route('login') }}">Back to login</a>
            </p>
        </form>
    </div>
</x-guest-layout>
