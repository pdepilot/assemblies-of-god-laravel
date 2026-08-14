    <form id="cmsLogoutForm" method="POST" action="{{ $cmsBootData['logout_url'] }}" hidden>
        @csrf
    </form>

    <div class="cms-loader" id="cmsLoader" aria-hidden="true">
        <div class="cms-loader__ring"></div>
        <p class="cms-loader__text">{{ $isSundaySchool ? 'Loading Sunday School' : 'Initializing Platform' }}</p>
    </div>

    <div id="cmsPageContent" style="display:none">
        @isset($header)
            <header class="cms-page__header">
                {{ $header }}
            </header>
        @endisset
        <div class="cms-page__body{{ $isCmsNativeShell ? '' : ' portal-module-content' }}">
            {{ $slot }}
        </div>
    </div>
