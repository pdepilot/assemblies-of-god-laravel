@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'auth-form-notice']) }} role="status">
        {{ $status }}
    </div>
@endif
