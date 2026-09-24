@php
    $tab = $activeTab ?: 'overview';
    $validTabs = collect($tabs)->pluck('id')->all();
    if (! in_array($tab, $validTabs, true)) {
        $tab = 'overview';
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="cms-page__title">Settings &amp; Administration Center</h1>
            <p class="cms-page__subtitle">Master control center for the Assemblies of God Ikenegbu Digital Ministry Platform — modules, permissions, integrations, security, and global configuration.</p>
        </div>
        <div class="cms-page__actions">
            @if ($isSuper ?? false)
                <span class="cms-badge cms-badge--gold"><i class="fas fa-crown" aria-hidden="true"></i> Super Admin</span>
            @endif
            @if ($canManage)
                <button type="button" class="cms-btn cms-btn--primary" id="saveActiveSettings">
                    <i class="fas fa-save" aria-hidden="true"></i> Save Changes
                </button>
            @endif
        </div>
    </x-slot>

    <div class="settings-center">
        @if (session('status'))
            <div class="cms-card" style="margin-bottom:16px;border-color:rgba(34,197,94,.35)">
                <div class="cms-card__body" style="color:#86efac">{{ session('status') }}</div>
            </div>
        @endif
        @if ($errors->any())
            <div class="cms-card" style="margin-bottom:16px;border-color:rgba(239,68,68,.35)">
                <div class="cms-card__body" style="color:#fca5a5">{{ $errors->first() }}</div>
            </div>
        @endif

        <div class="cms-grid cms-grid--4" style="margin-bottom:24px">
            <article class="cms-stat cms-card">
                <div class="cms-stat__top"><div class="cms-stat__icon cms-stat__icon--gold"><i class="fas fa-church" aria-hidden="true"></i></div></div>
                <div class="cms-stat__value" style="font-size:1.05rem;line-height:1.3">{{ $overview['short_name'] }}</div>
                <div class="cms-stat__label">Church</div>
            </article>
            <article class="cms-stat cms-card">
                <div class="cms-stat__top"><div class="cms-stat__icon cms-stat__icon--blue"><i class="fas fa-user-shield" aria-hidden="true"></i></div></div>
                <div class="cms-stat__value">{{ number_format($overview['admins']) }}</div>
                <div class="cms-stat__label">Administrators</div>
            </article>
            <article class="cms-stat cms-card">
                <div class="cms-stat__top"><div class="cms-stat__icon cms-stat__icon--green"><i class="fas fa-users" aria-hidden="true"></i></div></div>
                <div class="cms-stat__value">{{ number_format($overview['members']) }}</div>
                <div class="cms-stat__label">Members</div>
            </article>
            <article class="cms-stat cms-card">
                <div class="cms-stat__top"><div class="cms-stat__icon cms-stat__icon--purple"><i class="fas fa-key" aria-hidden="true"></i></div></div>
                <div class="cms-stat__value">{{ $overview['rbac_enabled'] ? 'On' : 'Off' }}</div>
                <div class="cms-stat__label">RBAC Enforcement</div>
            </article>
        </div>

        <nav class="cms-tabs cms-tabs--scroll" data-tabs role="tablist" aria-label="Settings sections">
            @foreach ($tabs as $item)
                <button type="button"
                        class="cms-tab{{ $tab === $item['id'] ? ' is-active' : '' }}"
                        data-tab="{{ $item['id'] }}"
                        role="tab"
                        aria-selected="{{ $tab === $item['id'] ? 'true' : 'false' }}">
                    <i class="fas {{ $item['icon'] }}" aria-hidden="true"></i>{{ $item['label'] }}
                </button>
            @endforeach
        </nav>

        {{-- Overview --}}
        <div class="cms-tab-panel{{ $tab === 'overview' ? ' is-active' : '' }}" data-panel="overview">
            <div class="cms-grid cms-grid--2">
                <article class="cms-card">
                    <div class="cms-card__head"><h2 class="cms-card__title"><i class="fas fa-gauge-high" aria-hidden="true"></i> Platform Overview</h2></div>
                    <div class="cms-card__body">
                        <dl style="display:grid;gap:10px;margin:0">
                            <div style="display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--cms-border,rgba(255,255,255,.08));padding-bottom:8px"><dt style="color:var(--cms-text-muted,#94a3b8)">Platform</dt><dd style="margin:0;font-weight:600">{{ $overview['platform_name'] }}</dd></div>
                            <div style="display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--cms-border,rgba(255,255,255,.08));padding-bottom:8px"><dt style="color:var(--cms-text-muted,#94a3b8)">Church</dt><dd style="margin:0;font-weight:600">{{ $overview['church_name'] }}</dd></div>
                            <div style="display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--cms-border,rgba(255,255,255,.08));padding-bottom:8px"><dt style="color:var(--cms-text-muted,#94a3b8)">Administrators</dt><dd style="margin:0;font-weight:600">{{ number_format($overview['admins']) }}</dd></div>
                            <div style="display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--cms-border,rgba(255,255,255,.08));padding-bottom:8px"><dt style="color:var(--cms-text-muted,#94a3b8)">Roles</dt><dd style="margin:0;font-weight:600">{{ number_format($overview['roles']) }}</dd></div>
                            <div style="display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--cms-border,rgba(255,255,255,.08));padding-bottom:8px"><dt style="color:var(--cms-text-muted,#94a3b8)">Members</dt><dd style="margin:0;font-weight:600">{{ number_format($overview['members']) }}</dd></div>
                            <div style="display:flex;justify-content:space-between;gap:12px"><dt style="color:var(--cms-text-muted,#94a3b8)">Admin sessions</dt><dd style="margin:0;font-weight:600">{{ number_format($overview['sessions']) }}</dd></div>
                        </dl>
                    </div>
                </article>
                <article class="cms-card">
                    <div class="cms-card__head"><h2 class="cms-card__title"><i class="fas fa-shield-halved" aria-hidden="true"></i> Security Snapshot</h2></div>
                    <div class="cms-card__body">
                        <dl style="display:grid;gap:10px;margin:0">
                            <div style="display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--cms-border,rgba(255,255,255,.08));padding-bottom:8px"><dt style="color:var(--cms-text-muted,#94a3b8)">RBAC enforcement</dt><dd style="margin:0;font-weight:600">{{ $overview['rbac_enabled'] ? 'Enabled' : 'Disabled (fail-open)' }}</dd></div>
                            <div style="display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--cms-border,rgba(255,255,255,.08));padding-bottom:8px"><dt style="color:var(--cms-text-muted,#94a3b8)">Signed-in as</dt><dd style="margin:0;font-weight:600">{{ $admin->display_name }}</dd></div>
                            <div style="display:flex;justify-content:space-between;gap:12px;border-bottom:1px solid var(--cms-border,rgba(255,255,255,.08));padding-bottom:8px"><dt style="color:var(--cms-text-muted,#94a3b8)">Role</dt><dd style="margin:0;font-weight:600">{{ ucfirst(str_replace('_', ' ', (string) $admin->role)) }}</dd></div>
                            <div style="display:flex;justify-content:space-between;gap:12px"><dt style="color:var(--cms-text-muted,#94a3b8)">Theme</dt><dd style="margin:0;font-weight:600">{{ $admin->ui_theme }}/{{ $admin->ui_mode }}</dd></div>
                        </dl>
                    </div>
                </article>
            </div>
        </div>

        {{-- General --}}
        <div class="cms-tab-panel{{ $tab === 'general' ? ' is-active' : '' }}" data-panel="general">
            <article class="cms-card">
                <div class="cms-card__head"><h2 class="cms-card__title">General Settings</h2></div>
                <div class="cms-card__body">
                    @if ($canManage)
                        <form method="POST" action="{{ route('settings.group.update') }}" id="settingsFormGeneral" data-settings-form="general">
                            @csrf
                            <input type="hidden" name="group" value="general">
                            <div class="cms-form-row cms-form-row--settings">
                                <div class="cms-field">
                                    <label for="timezone">Timezone</label>
                                    <select id="timezone" name="timezone">
                                        @foreach (['Africa/Lagos' => 'Africa/Lagos (WAT)', 'Africa/Accra' => 'Africa/Accra (GMT)', 'Europe/London' => 'Europe/London', 'America/New_York' => 'America/New_York'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('timezone', $groups['general']['timezone'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="cms-field">
                                    <label for="language">Language</label>
                                    <select id="language" name="language">
                                        @foreach (['en' => 'English', 'ig' => 'Igbo', 'pcm' => 'Nigerian Pidgin'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('language', $groups['general']['language'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="cms-field">
                                    <label for="currency">Currency</label>
                                    <select id="currency" name="currency">
                                        @foreach (['NGN' => 'NGN (₦)', 'USD' => 'USD ($)', 'GBP' => 'GBP (£)'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('currency', $groups['general']['currency'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="cms-field">
                                    <label for="date_format">Date Format</label>
                                    <select id="date_format" name="date_format">
                                        @foreach (['dmy' => 'DD/MM/YYYY', 'mdy' => 'MM/DD/YYYY', 'ymd' => 'YYYY-MM-DD'] as $value => $label)
                                            <option value="{{ $value }}" @selected(old('date_format', $groups['general']['date_format'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="cms-field">
                                    <label for="records_per_page">Records Per Page</label>
                                    <input id="records_per_page" type="number" min="5" max="100" name="records_per_page" value="{{ old('records_per_page', $groups['general']['records_per_page'] ?? 25) }}">
                                </div>
                            </div>
                        </form>
                    @else
                        <p style="color:var(--cms-text-muted);margin:0">View only. Ask an administrator to update general settings.</p>
                    @endif
                </div>
            </article>
        </div>

        {{-- Church --}}
        <div class="cms-tab-panel{{ $tab === 'church' ? ' is-active' : '' }}" data-panel="church">
            <article class="cms-card">
                <div class="cms-card__head"><h2 class="cms-card__title">Church Profile</h2></div>
                <div class="cms-card__body">
                    @if ($canManage)
                        <form method="POST" action="{{ route('settings.group.update') }}" id="settingsFormChurch" data-settings-form="church" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="group" value="church">
                            <div class="cms-form-row cms-form-row--settings">
                                @foreach ([
                                    'name' => ['Church Name', 'text'],
                                    'short_name' => ['Short Name', 'text'],
                                    'pastor' => ['Pastor / Lead Minister', 'text'],
                                    'founded_year' => ['Founded Year', 'number'],
                                    'address' => ['Address', 'textarea'],
                                    'phone' => ['Phone', 'text'],
                                    'email' => ['Email', 'email'],
                                    'website' => ['Website URL', 'url'],
                                    'service_sunday' => ['Sunday Service Times', 'text'],
                                    'service_midweek' => ['Midweek Service', 'text'],
                                    'social_facebook' => ['Facebook', 'url'],
                                    'social_instagram' => ['Instagram', 'url'],
                                    'social_youtube' => ['YouTube', 'url'],
                                ] as $field => [$label, $type])
                                    <div class="cms-field{{ $type === 'textarea' || $field === 'name' ? ' cms-field--full' : '' }}">
                                        <label for="church_{{ $field }}">{{ $label }}</label>
                                        @if ($type === 'textarea')
                                            <textarea id="church_{{ $field }}" name="{{ $field }}" rows="2">{{ old($field, $groups['church'][$field] ?? '') }}</textarea>
                                        @else
                                            <input id="church_{{ $field }}" type="{{ $type === 'url' ? 'url' : $type }}" name="{{ $field }}" value="{{ old($field, $groups['church'][$field] ?? '') }}">
                                        @endif
                                    </div>
                                @endforeach

                                <div class="cms-field cms-field--full">
                                    <label for="church_logo">Church logo</label>
                                    @if (! empty($groups['church']['logo_url']))
                                        <div style="margin:8px 0">
                                            <img src="{{ $groups['church']['logo_url'] }}" alt="Church logo" style="max-height:80px;max-width:220px;object-fit:contain;border-radius:8px;background:#fff;padding:6px">
                                        </div>
                                        <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.9rem;margin-bottom:8px">
                                            <input type="checkbox" name="remove_logo" value="1" @checked(old('remove_logo'))>
                                            Remove current logo
                                        </label>
                                    @endif
                                    <input id="church_logo" type="file" name="logo" accept="image/jpeg,image/png,image/webp">
                                    <p style="margin:6px 0 0;font-size:0.82rem;color:var(--cms-text-muted,#94a3b8)">JPG, PNG, or WebP up to 5 MB. Upload only — no URL/path.</p>
                                    @error('logo')
                                        <p style="color:#f87171;margin:6px 0 0;font-size:0.85rem">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </form>
                    @else
                        <p style="color:var(--cms-text-muted);margin:0">View only.</p>
                        @if (! empty($groups['church']['logo_url']))
                            <div style="margin-top:12px">
                                <img src="{{ $groups['church']['logo_url'] }}" alt="Church logo" style="max-height:80px;max-width:220px;object-fit:contain;border-radius:8px;background:#fff;padding:6px">
                            </div>
                        @endif
                    @endif
                </div>
            </article>
        </div>

        {{-- Website design --}}
        <div class="cms-tab-panel{{ $tab === 'website_design' ? ' is-active' : '' }}" data-panel="website_design">
            <article class="cms-card">
                <div class="cms-card__head"><h2 class="cms-card__title">Website Design &amp; Colors</h2></div>
                <div class="cms-card__body">
                    <p style="color:var(--cms-text-muted);margin-bottom:16px">Update the global website color palette used across public pages. Changes apply to all visitors.</p>
                    @if ($canManage)
                        <form method="POST" action="{{ route('settings.group.update') }}" id="settingsFormWebsiteDesign" data-settings-form="website_design">
                            @csrf
                            <input type="hidden" name="group" value="website_design">
                            <div class="cms-form-row cms-form-row--settings">
                                @foreach ([
                                    'primary_color' => 'Primary',
                                    'secondary_color' => 'Secondary',
                                    'dark_color' => 'Dark',
                                    'text_color' => 'Text',
                                    'background_color' => 'Background',
                                    'accent_color' => 'Accent',
                                ] as $field => $label)
                                    <div class="cms-field">
                                        <label for="{{ $field }}">{{ $label }}</label>
                                        <input id="{{ $field }}" type="color" name="{{ $field }}" value="{{ old($field, $groups['website_design'][$field] ?? '#000000') }}">
                                    </div>
                                @endforeach
                            </div>
                        </form>
                    @else
                        <p style="color:var(--cms-text-muted);margin:0">View only.</p>
                    @endif
                </div>
            </article>
        </div>

        {{-- Preferences --}}
        <div class="cms-tab-panel{{ $tab === 'preferences' ? ' is-active' : '' }}" data-panel="preferences">
            <article class="cms-card">
                <div class="cms-card__head"><h2 class="cms-card__title">My Preferences</h2></div>
                <div class="cms-card__body">
                    <form method="POST" action="{{ route('settings.preferences.update') }}" id="settingsFormPreferences" data-settings-form="preferences">
                        @csrf
                        <div class="cms-form-row cms-form-row--settings">
                            <div class="cms-field cms-field--full">
                                <label for="full_name">Full name</label>
                                <input id="full_name" name="full_name" value="{{ old('full_name', $admin->full_name) }}" required>
                            </div>
                            <div class="cms-field">
                                <label for="phone">Phone</label>
                                <input id="phone" name="phone" value="{{ old('phone', $admin->phone) }}">
                            </div>
                            <div class="cms-field">
                                <label for="ui_theme">Theme</label>
                                <select id="ui_theme" name="ui_theme">
                                    @foreach (['gold', 'blue', 'emerald', 'rose'] as $theme)
                                        <option value="{{ $theme }}" @selected(old('ui_theme', $admin->ui_theme) === $theme)>{{ ucfirst($theme) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="cms-field">
                                <label for="ui_mode">Mode</label>
                                <select id="ui_mode" name="ui_mode">
                                    @foreach (['dark', 'light'] as $mode)
                                        <option value="{{ $mode }}" @selected(old('ui_mode', $admin->ui_mode) === $mode)>{{ ucfirst($mode) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
            </article>
        </div>

        {{-- Roles / RBAC --}}
        <div class="cms-tab-panel{{ $tab === 'roles' ? ' is-active' : '' }}" data-panel="roles">
            @if ($canManageRbac && is_array($rbacPanel ?? null))
                @include('settings._roles-panel')
            @elseif ($canManageRbac)
                <article class="cms-card">
                    <div class="cms-card__body">
                        <p style="color:var(--cms-text-muted);margin:0">Unable to load role data. Confirm RBAC tables are migrated.</p>
                    </div>
                </article>
            @else
                <article class="cms-card">
                    <div class="cms-card__body">
                        <p style="color:var(--cms-text-muted);margin:0">Super Administrator access is required to manage roles and permissions.</p>
                    </div>
                </article>
            @endif
        </div>

        {{-- Administrators --}}
        <div class="cms-tab-panel{{ $tab === 'admins' ? ' is-active' : '' }}" data-panel="admins">
            @include('settings._admins-panel')
        </div>

        {{-- Communication --}}
        <div class="cms-tab-panel{{ $tab === 'communication' ? ' is-active' : '' }}" data-panel="communication">
            <article class="cms-card">
                <div class="cms-card__head"><h2 class="cms-card__title">Communication</h2></div>
                <div class="cms-card__body" style="display:grid;gap:12px">
                    <p style="color:var(--cms-text-muted);margin:0">Manage outbound messaging settings in the Communication Hub modules.</p>
                    <div style="display:flex;flex-wrap:wrap;gap:10px">
                        <a class="cms-btn cms-btn--primary" href="{{ route('communication-hub.settings.edit') }}"><i class="fas fa-sliders" aria-hidden="true"></i> Email / SMS settings</a>
                        <a class="cms-btn cms-btn--ghost" href="{{ route('communication-hub.birthdays.index') }}"><i class="fas fa-cake-candles" aria-hidden="true"></i> Birthday Calendar</a>
                        <a class="cms-btn cms-btn--ghost" href="{{ route('communication-hub.email-center.index') }}"><i class="fas fa-envelope-open-text" aria-hidden="true"></i> Email Center</a>
                        <a class="cms-btn cms-btn--ghost" href="{{ route('communication-hub.templates.index') }}"><i class="fas fa-file-lines" aria-hidden="true"></i> Templates</a>
                    </div>
                </div>
            </article>
        </div>

        {{-- Related --}}
        <div class="cms-tab-panel{{ $tab === 'related' ? ' is-active' : '' }}" data-panel="related">
            <article class="cms-card">
                <div class="cms-card__head"><h2 class="cms-card__title">More Modules</h2></div>
                <div class="cms-card__body" style="display:grid;gap:10px">
                    <a class="cms-btn cms-btn--ghost" style="justify-content:flex-start" href="{{ route('ministries.settings.index') }}"><i class="fas fa-sliders" aria-hidden="true"></i> Ministry settings</a>
                    <a class="cms-btn cms-btn--ghost" style="justify-content:flex-start" href="{{ route('analytics.cutover.index') }}"><i class="fas fa-route" aria-hidden="true"></i> Migration cutover</a>
                    <a class="cms-btn cms-btn--ghost" style="justify-content:flex-start" href="{{ route('analytics.site-traffic.index') }}"><i class="fas fa-chart-area" aria-hidden="true"></i> Site traffic</a>
                    <a class="cms-btn cms-btn--ghost" style="justify-content:flex-start" href="{{ route('website.seo.index') }}"><i class="fas fa-magnifying-glass-chart" aria-hidden="true"></i> SEO manager</a>
                </div>
            </article>
        </div>
    </div>

    <script>
        (function () {
            var headerSave = document.getElementById('saveActiveSettings');
            if (headerSave) {
                headerSave.addEventListener('click', function () {
                    var panel = document.querySelector('.cms-tab-panel.is-active');
                    var form = panel ? panel.querySelector('form[data-settings-form]') : null;
                    if (form) {
                        form.requestSubmit();
                    }
                });
            }

            document.querySelectorAll('.cms-tab').forEach(function (tab) {
                tab.addEventListener('click', function () {
                    var target = tab.getAttribute('data-tab');
                    if (!target) return;
                    var url = new URL(window.location.href);
                    url.searchParams.set('tab', target);
                    window.history.replaceState({}, '', url.toString());
                });
            });
        })();
    </script>
</x-app-layout>
