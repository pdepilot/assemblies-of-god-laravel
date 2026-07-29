@php
    $active = $navActive ?? 'home';
    $navClass = fn (string $key) => 'nav-item nav-link'.($active === $key ? ' active' : '');
@endphp
<div class="navbar-nav ms-lg-auto mx-xl-auto">
    <a href="{{ route('public.home') }}" class="{{ $navClass('home') }}">Home</a>
    <a href="{{ route('public.about') }}" class="{{ $navClass('about') }}">About Us</a>
    <a href="{{ route('public.activity') }}" class="{{ $navClass('ministries') }}">Ministries</a>
    <a href="{{ route('public.event') }}" class="{{ $navClass('events') }}">Events</a>
    <a href="{{ route('public.blog') }}" class="{{ $navClass('blog') }}">Blog</a>
    <a href="{{ route('public.sermons') }}" class="{{ $navClass('sermons') }}">Sermons</a>
    <a href="{{ route('public.contact') }}" class="{{ $navClass('contact') }}">Contact</a>
</div>
