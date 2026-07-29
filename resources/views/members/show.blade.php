<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">{{ $member['full_name'] }}</h2>
            <div class="flex gap-3 text-sm">
                @if ($canManage)
                    <a href="{{ route('members.edit', $member['id']) }}" class="text-indigo-600 hover:underline">Edit</a>
                @endif
                <a href="{{ route('members.index') }}" class="text-indigo-600 hover:underline">Back to directory</a>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6">
                <div class="member-profile">
                    <div
                        class="member-profile__photo{{ ! empty($member['photo_url']) ? ' is-clickable' : '' }}"
                        @if (! empty($member['photo_url']))
                            id="memberViewPhoto"
                            role="button"
                            tabindex="0"
                            title="Click to view full photo"
                            aria-label="View full photo of {{ $member['full_name'] }}"
                            data-photo-url="{{ $member['photo_url'] }}"
                            data-photo-alt="{{ $member['full_name'] }}"
                        @endif
                    >
                        @if (! empty($member['photo_url']))
                            <img src="{{ $member['photo_url'] }}" alt="{{ $member['full_name'] }}" loading="lazy">
                        @else
                            <span class="member-profile__initials">{{ strtoupper(\Illuminate\Support\Str::substr($member['full_name'] ?? 'M', 0, 1)) }}</span>
                        @endif
                    </div>
                    <div class="member-profile__intro">
                        <h3 class="member-profile__name">{{ $member['full_name'] }}</h3>
                        <p class="member-profile__meta">
                            <span class="font-mono">{{ $member['member_code'] }}</span>
                            · {{ $member['department'] ?: 'No department' }}
                            · {{ $statusLabels[$member['status']] ?? $member['status'] }}
                        </p>
                    </div>
                </div>

                <dl class="grid gap-4 sm:grid-cols-2">
                    <div><dt class="text-sm text-gray-500">Member ID</dt><dd class="font-mono font-medium">{{ $member['member_code'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Status</dt><dd>{{ $statusLabels[$member['status']] ?? $member['status'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Phone</dt><dd>{{ $member['phone'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Email</dt><dd>{{ $member['email'] ?? '—' }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Department</dt><dd>{{ $member['department'] }}</dd></div>
                    <div><dt class="text-sm text-gray-500">Joined</dt><dd>{{ $member['joined_date'] }}</dd></div>
                    <div class="sm:col-span-2"><dt class="text-sm text-gray-500">Address</dt><dd>{{ $member['address'] }}</dd></div>
                    @if (! empty($member['notes']))
                        <div class="sm:col-span-2"><dt class="text-sm text-gray-500">Notes</dt><dd>{{ $member['notes'] }}</dd></div>
                    @endif
                </dl>
            </div>
        </div>
    </div>

    @if (! empty($member['photo_url']))
        <div class="cms-photo-lightbox" id="memberPhotoLightbox" aria-hidden="true" role="dialog" aria-label="Member photo">
            <button type="button" class="cms-photo-lightbox__close" id="memberPhotoLightboxClose" aria-label="Close photo">&times;</button>
            <img src="" alt="" id="memberPhotoLightboxImg">
        </div>
        <script>
            (function () {
                var trigger = document.getElementById('memberViewPhoto');
                var box = document.getElementById('memberPhotoLightbox');
                var img = document.getElementById('memberPhotoLightboxImg');
                var closeBtn = document.getElementById('memberPhotoLightboxClose');
                if (!trigger || !box || !img) return;

                function openLightbox() {
                    var url = trigger.getAttribute('data-photo-url');
                    if (!url) return;
                    img.src = url;
                    img.alt = trigger.getAttribute('data-photo-alt') || 'Member photo';
                    box.classList.add('is-open');
                    box.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('cms-modal-open');
                }

                function closeLightbox() {
                    box.classList.remove('is-open');
                    box.setAttribute('aria-hidden', 'true');
                    img.removeAttribute('src');
                    img.alt = '';
                    document.body.classList.remove('cms-modal-open');
                }

                trigger.addEventListener('click', openLightbox);
                trigger.addEventListener('keydown', function (e) {
                    if (e.key !== 'Enter' && e.key !== ' ') return;
                    e.preventDefault();
                    openLightbox();
                });
                box.addEventListener('click', function (e) {
                    if (e.target === box || e.target === closeBtn) closeLightbox();
                });
                if (closeBtn) closeBtn.addEventListener('click', closeLightbox);
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && box.classList.contains('is-open')) closeLightbox();
                });
            })();
        </script>
    @endif
</x-app-layout>
