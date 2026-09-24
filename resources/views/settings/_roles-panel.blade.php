{{-- Roles & Permissions management panel --}}
@php
    /** @var array<string, mixed> $rbacPanel */
    $rbacPanel = is_array($rbacPanel ?? null) ? $rbacPanel : [];
    $selectedAdmin = is_array($rbacPanel['selected_admin'] ?? null) ? $rbacPanel['selected_admin'] : null;
    $selectedRoleIds = is_array($selectedAdmin['role_ids'] ?? null) ? $selectedAdmin['role_ids'] : [];
@endphp

<article class="cms-card" style="margin-bottom:16px">
    <div class="cms-card__head"><h2 class="cms-card__title">1. Access control mode</h2></div>
    <div class="cms-card__body">
        <p style="color:var(--cms-text-muted);margin:0 0 16px">
            When enforcement is <strong>off</strong>, every admin can open the full portal (safe default).
            When it is <strong>on</strong>, each admin only sees sidebar links and pages allowed by their role permissions.
            Turn it on only after roles and assignments below look correct.
        </p>
        @if ($canManageRbac)
            <form method="POST" action="{{ route('settings.group.update') }}" id="settingsFormRoles" data-settings-form="roles">
                @csrf
                <input type="hidden" name="group" value="rbac">
                <div class="cms-form-row cms-form-row--settings">
                    <div class="cms-field">
                        <label>
                            <input type="hidden" name="enforcement_enabled" value="0">
                            <input type="checkbox" name="enforcement_enabled" value="1" @checked(old('enforcement_enabled', $groups['rbac']['enforcement_enabled'] ?? false))>
                            Enable RBAC enforcement
                        </label>
                    </div>
                    <div class="cms-field">
                        <label>
                            <input type="hidden" name="debug_enabled" value="0">
                            <input type="checkbox" name="debug_enabled" value="1" @checked(old('debug_enabled', $groups['rbac']['debug_enabled'] ?? false))>
                            Enable RBAC debug mode
                        </label>
                    </div>
                </div>
                <button type="submit" class="cms-btn cms-btn--primary" style="margin-top:8px">Save access mode</button>
            </form>
        @else
            <p style="color:var(--cms-text-muted);margin:0">Super Administrator access is required to change this.</p>
        @endif
    </div>
</article>

<article class="cms-card" style="margin-bottom:16px">
    <div class="cms-card__head" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px">
        <h2 class="cms-card__title" style="margin:0">2. Assign roles to an administrator</h2>
        <span style="font-size:0.85rem;color:var(--cms-text-muted)">{{ number_format(count($rbacPanel['admins'] ?? [])) }} admins · {{ number_format(count($rbacPanel['roles'] ?? [])) }} roles</span>
    </div>
    <div class="cms-card__body">
        <p style="color:var(--cms-text-muted);margin:0 0 16px">
            Pick an admin, tick the roles they should have, then save. One admin can hold multiple roles.
        </p>

        @if ($canManageRbac && ! empty($rbacPanel['admins']))
            <form method="GET" action="{{ route('settings.index') }}" style="margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px;align-items:end">
                <input type="hidden" name="tab" value="roles">
                <div class="cms-field" style="min-width:260px;flex:1">
                    <label for="assign_admin">Administrator</label>
                    <select id="assign_admin" name="assign_admin" onchange="this.form.submit()">
                        @foreach ($rbacPanel['admins'] as $adminOption)
                            <option value="{{ $adminOption['id'] }}" @selected((int) ($selectedAdmin['id'] ?? 0) === (int) $adminOption['id'])>
                                {{ $adminOption['full_name'] }} ({{ $adminOption['email'] }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </form>

            @if ($selectedAdmin)
                <form method="POST" action="{{ route('settings.rbac.assignments.sync') }}">
                    @csrf
                    <input type="hidden" name="admin_id" value="{{ $selectedAdmin['id'] }}">

                    <div style="margin-bottom:12px;padding:12px;border:1px solid var(--cms-border,rgba(255,255,255,.08));border-radius:10px">
                        <div style="font-weight:600">{{ $selectedAdmin['full_name'] }}</div>
                        <div style="font-size:0.85rem;color:var(--cms-text-muted)">{{ $selectedAdmin['email'] }} · account role: {{ $selectedAdmin['role'] }}</div>
                        @if (! empty($selectedAdmin['role_names']))
                            <div style="margin-top:8px;display:flex;flex-wrap:wrap">
                                @foreach ($selectedAdmin['role_names'] as $roleName)
                                    <span class="cms-badge" style="background:rgba(201,162,39,.15);color:var(--cms-gold,#c9a227);padding:2px 8px;border-radius:999px;font-size:0.75rem;margin:0 6px 6px 0">{{ $roleName }}</span>
                                @endforeach
                            </div>
                        @else
                            <div style="margin-top:8px;font-size:0.85rem;color:var(--cms-text-muted)">No RBAC roles assigned yet.</div>
                        @endif
                    </div>

                    <div style="display:grid;gap:8px;max-height:320px;overflow:auto;padding-right:4px;margin-bottom:14px">
                        @foreach ($rbacPanel['roles'] as $roleOption)
                            <label style="display:flex;gap:10px;align-items:flex-start;padding:10px;border:1px solid var(--cms-border,rgba(255,255,255,.08));border-radius:8px;cursor:pointer">
                                <input
                                    type="checkbox"
                                    name="role_ids[]"
                                    value="{{ $roleOption['id'] }}"
                                    @checked(in_array((int) $roleOption['id'], array_map('intval', $selectedRoleIds), true))
                                    style="margin-top:3px"
                                >
                                <span>
                                    <strong>{{ $roleOption['name'] }}</strong>
                                    <span style="display:block;font-size:0.8rem;color:var(--cms-text-muted)">
                                        {{ $roleOption['dashboard_label'] }}
                                        · {{ number_format((int) $roleOption['permission_count']) }} permissions
                                        @if (! empty($roleOption['is_system'])) · system @endif
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <button type="submit" class="cms-btn cms-btn--primary">Save role assignment</button>
                </form>
            @endif
        @elseif ($canManageRbac)
            <p style="color:var(--cms-text-muted);margin:0">No active administrators found to assign.</p>
        @else
            <p style="color:var(--cms-text-muted);margin:0">Super Administrator access is required to assign roles.</p>
        @endif
    </div>
</article>

<article class="cms-card">
    <div class="cms-card__head" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px">
        <h2 class="cms-card__title" style="margin:0">3. Roles &amp; permissions</h2>
        @if ($canManageRbac)
            <a href="{{ route('settings.rbac.roles.create') }}" class="cms-btn cms-btn--primary cms-btn--sm"><i class="fas fa-plus" aria-hidden="true"></i> Create role</a>
        @endif
    </div>
    <div class="cms-card__body">
        <p style="color:var(--cms-text-muted);margin:0 0 16px">
            Edit a role to choose its dashboard and tick the permissions it grants.
            Catalog: <strong>{{ number_format((int) ($rbacPanel['permission_count'] ?? 0)) }}</strong> permissions available.
        </p>

        <div class="cms-table-wrap">
            <table class="cms-table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Dashboard</th>
                        <th>Admins</th>
                        <th>Permissions</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse (($rbacPanel['roles'] ?? []) as $roleRow)
                        <tr>
                            <td>
                                <strong>{{ $roleRow['name'] }}</strong>
                                <div style="font-size:0.78rem;color:var(--cms-text-muted);font-family:monospace">{{ $roleRow['slug'] }}</div>
                            </td>
                            <td>{{ $roleRow['dashboard_label'] }}</td>
                            <td>{{ number_format((int) $roleRow['admin_count']) }}</td>
                            <td>{{ number_format((int) $roleRow['permission_count']) }}</td>
                            <td>
                                @if (! empty($roleRow['is_active']))
                                    <span style="color:#34d399">Active</span>
                                @else
                                    <span style="color:#f87171">Inactive</span>
                                @endif
                                @if (! empty($roleRow['is_system']))
                                    <div style="font-size:0.75rem;color:var(--cms-text-muted)">System</div>
                                @endif
                            </td>
                            <td style="white-space:nowrap">
                                @if ($canManageRbac)
                                    <a href="{{ route('settings.rbac.roles.edit', $roleRow['id']) }}" class="cms-btn cms-btn--ghost cms-btn--sm">Edit</a>
                                    @unless (! empty($roleRow['is_system']))
                                        <form method="POST" action="{{ route('settings.rbac.roles.destroy', $roleRow['id']) }}" style="display:inline" data-confirm="Delete this role? Assignments will be removed." data-confirm-title="Delete role" data-confirm-ok="Delete" data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cms-btn cms-btn--ghost cms-btn--sm" style="color:#f87171">Delete</button>
                                        </form>
                                    @endunless
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="cms-table__empty">No roles found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</article>
