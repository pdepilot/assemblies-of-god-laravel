<x-guest-layout>
    <div id="authLoginWrap">
        <div class="auth-welcome">
            <p class="auth-welcome__label">{{ config('identity.admin.login_title', 'Administrator Portal') }}</p>
            <h2 class="auth-welcome__title">Welcome Back</h2>
            <div class="auth-welcome__rotate" aria-live="polite" aria-atomic="true"></div>
        </div>

        <form id="authForm" class="auth-form" method="POST" action="{{ route('login') }}">
            @csrf

            @if ($errors->any())
                <div id="authFormError" class="auth-form-error" role="alert">
                    {{ $errors->first() }}
                </div>
            @elseif (request('reason') === 'inactivity')
                <div id="authFormError" class="auth-form-error" role="alert">
                    You have been logged out due to inactivity.
                </div>
            @else
                <div id="authFormError" class="auth-form-error" role="alert" hidden></div>
            @endif

            <x-auth-session-status class="auth-form-notice" :status="session('status')" />

            <div class="auth-field @error('email') is-error @enderror">
                <input type="email" id="authEmail" name="email" class="auth-field__input" placeholder=" " value="{{ old('email') }}" autocomplete="email" required autofocus aria-describedby="authEmailError">
                <label class="auth-field__label" for="authEmail">Email Address</label>
                <p id="authEmailError" class="auth-field__error" role="alert">Please enter a valid email address.</p>
            </div>

            <div class="auth-field @error('password') is-error @enderror">
                <input type="password" id="authPassword" name="password" class="auth-field__input" placeholder=" " autocomplete="current-password" required aria-describedby="authPasswordError">
                <label class="auth-field__label" for="authPassword">Password</label>
                <button type="button" id="authPasswordToggle" class="auth-field__toggle" aria-label="Show password">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
                <p id="authPasswordError" class="auth-field__error" role="alert">Password must be at least 6 characters.</p>
            </div>

            <div class="auth-form__meta">
                <a class="auth-link" href="{{ route('password.request') }}">Forgot password?</a>
            </div>

            <button type="submit" id="authSubmit" class="auth-submit">
                <span class="auth-submit__content">
                    <span class="auth-submit__loader" aria-hidden="true"></span>
                    <i class="fas fa-lock auth-submit__text" aria-hidden="true"></i>
                    <span class="auth-submit__text">Secure Login</span>
                </span>
            </button>

            <p class="auth-form__footer">
                Protected by enterprise encryption · <a href="{{ rtrim((string) config('portal.media_base'), '/') }}/">Return to {{ config('identity.public.short_name', 'Website') }}</a>
            </p>
        </form>
    </div>
</x-guest-layout>
