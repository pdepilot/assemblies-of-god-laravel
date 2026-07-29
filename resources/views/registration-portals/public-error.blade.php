<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} | AG Ikenebgu</title>
    <link rel="stylesheet" href="{{ asset('site/css/registration-portal.css') }}">
</head>
<body class="rp-public">
<main class="rp-public__main">
    <section class="rp-public__card rp-public__card--error">
        <h1>{{ $title }}</h1>
        <p>{{ $message }}</p>
        @if (!empty($hint))
            <p class="rp-public__hint">{{ $hint }}</p>
        @endif
        <a class="rp-public__home-link" href="{{ url('/') }}">Return to church website</a>
    </section>
</main>
</body>
</html>
