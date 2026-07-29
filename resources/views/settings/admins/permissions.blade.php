<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="cms-page__title">Permissions · {{ $managedAdmin['full_name'] }}</h1>
            <p class="cms-page__subtitle">{{ $managedAdmin['email'] }} · {{ $access_label }}</p>
        </div>
        <div class="cms-page__actions">
            <a href="{{ route('settings.admins.edit', $managedAdmin['id']) }}" class="cms-btn cms-btn--ghost">Edit account</a>
            <a href="{{ route('settings.index', ['tab' => 'roles', 'assign_admin' => $managedAdmin['id']]) }}" class="cms-btn cms-btn--primary">Change roles</a>
            <a href="{{ route('settings.index', ['tab' => 'admins']) }}" class="cms-btn cms-btn--ghost">Back to Admins</a>
        </div>
    </x-slot>

    <div class="cms-grid cms-grid--3" style="margin-bottom:16px">
        <article class="cms-stat cms-card">
            <div class="cms-stat__value">{{ number_format((int) $permission_count) }}</div>
            <div class="cms-stat__label">Effective permissions</div>
        </article>
        <article class="cms-stat cms-card">
            <div class="cms-stat__value">{{ number_format(count($roles)) }}</div>
            <div class="cms-stat__label">Assigned roles</div>
        </article>
        <article class="cms-stat cms-card">
            <div class="cms-stat__value" style="font-size:1rem;line-height:1.35">{{ $access_label }}</div>
            <div class="cms-stat__label">Access mode</div>
        </article>
    </div>

    <article class="cms-card" style="margin-bottom:16px">
        <div class="cms-card__head"><h2 class="cms-card__title">Assigned roles</h2></div>
        <div class="cms-card__body">
            @if ($roles === [])
                <p style="margin:0;color:var(--cms-text-muted)">No RBAC roles assigned.</p>
            @else
                <div class="cms-table-wrap">
                    <table class="cms-table">
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Slug</th>
                                <th>Permissions in role</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $role)
                                <tr>
                                    <td><strong>{{ $role['name'] }}</strong></td>
                                    <td style="font-family:monospace;font-size:0.85rem">{{ $role['slug'] }}</td>
                                    <td>{{ number_format((int) $role['permission_count']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </article>

    <article class="cms-card">
        <div class="cms-card__head"><h2 class="cms-card__title">Effective permissions</h2></div>
        <div class="cms-card__body">
            @if (in_array($access_mode, ['full_super', 'full_legacy'], true))
                <p style="margin:0 0 16px;color:var(--cms-text-muted)">
                    This account currently has unrestricted platform access
                    @if ($access_mode === 'full_super') as Super Admin
                    @else as a legacy full-access admin
                    @endif.
                    Listed below are any explicit role/direct permissions still recorded for reference.
                </p>
            @endif

            @if ($modules === [])
                <p style="margin:0;color:var(--cms-text-muted)">No explicit permissions found for this administrator.</p>
            @else
                <div style="display:grid;gap:16px">
                    @foreach ($modules as $module)
                        <section style="border:1px solid var(--cms-border,rgba(255,255,255,.08));border-radius:10px;padding:12px">
                            <h3 style="margin:0 0 10px;font-size:1rem">
                                {{ $module['label'] }}
                                <span style="font-size:0.8rem;color:var(--cms-text-muted);font-weight:400">
                                    · {{ number_format(count($module['permissions'])) }}
                                </span>
                            </h3>
                            <div class="cms-table-wrap">
                                <table class="cms-table">
                                    <thead>
                                        <tr>
                                            <th>Permission</th>
                                            <th>Key</th>
                                            <th>Granted by</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($module['permissions'] as $perm)
                                            <tr>
                                                <td>{{ $perm['label'] }}</td>
                                                <td style="font-family:monospace;font-size:0.8rem">{{ $perm['permission_key'] }}</td>
                                                <td style="font-size:0.85rem;color:var(--cms-text-muted)">
                                                    {{ implode(', ', $perm['sources']) }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </article>
</x-app-layout>
