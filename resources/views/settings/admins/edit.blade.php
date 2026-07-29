<x-app-layout>
    @php
        $isEdit = is_array($managedAdmin ?? null);
    @endphp

    <x-slot name="header">
        <div>
            <h1 class="cms-page__title">{{ $isEdit ? 'Edit administrator' : 'Add administrator' }}</h1>
            <p class="cms-page__subtitle">
                {{ $isEdit ? 'Update account details, status, role, or password.' : 'Create a new admin login and choose their project role.' }}
            </p>
        </div>
        <div class="cms-page__actions">
            <a href="{{ route('settings.index', ['tab' => 'admins']) }}" class="cms-btn cms-btn--ghost">Back to Admins</a>
        </div>
    </x-slot>

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

    <article class="cms-card">
        <div class="cms-card__body">
            <form
                method="POST"
                action="{{ $isEdit ? route('settings.admins.update', $managedAdmin['id']) : route('settings.admins.store') }}"
            >
                @csrf
                @if ($isEdit) @method('PUT') @endif

                <div class="cms-form-row cms-form-row--settings" style="display:grid;gap:14px;grid-template-columns:repeat(auto-fit,minmax(220px,1fr))">
                    <div class="cms-field">
                        <label for="full_name">Full name</label>
                        <input id="full_name" name="full_name" required maxlength="191" value="{{ old('full_name', $managedAdmin['full_name'] ?? '') }}">
                    </div>
                    <div class="cms-field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" required maxlength="191" value="{{ old('email', $managedAdmin['email'] ?? '') }}">
                    </div>
                    <div class="cms-field">
                        <label for="username">Username</label>
                        <input id="username" name="username" maxlength="80" value="{{ old('username', $managedAdmin['username'] ?? '') }}" placeholder="Optional">
                    </div>
                    <div class="cms-field">
                        <label for="phone">Phone</label>
                        <input id="phone" name="phone" maxlength="30" value="{{ old('phone', $managedAdmin['phone'] ?? '') }}">
                    </div>
                    <div class="cms-field">
                        <label for="department">Department</label>
                        <input id="department" name="department" maxlength="120" value="{{ old('department', $managedAdmin['department'] ?? '') }}">
                    </div>
                    <div class="cms-field">
                        <label for="position">Position</label>
                        <input id="position" name="position" maxlength="120" value="{{ old('position', $managedAdmin['position'] ?? '') }}">
                    </div>
                    <div class="cms-field">
                        <label for="rbac_role_id">Role</label>
                        @php
                            $selectedRoleId = (int) old(
                                'rbac_role_id',
                                $managedAdmin['role_id']
                                    ?? ($managedAdmin['role_ids'][0] ?? 0)
                            );
                            $superRole = collect($assignableRoles)->firstWhere('slug', 'super_admin');
                            $superRoleId = (int) ($superRole['id'] ?? $selectedRoleId);
                        @endphp
                        @if ($isEdit && ! empty($managedAdmin['is_super_admin']))
                            <input type="text" value="Super Admin" disabled>
                            @if ($superRoleId > 0)
                                <input type="hidden" name="rbac_role_id" value="{{ old('rbac_role_id', $superRoleId) }}">
                            @endif
                            <p style="margin:6px 0 0;font-size:0.8rem;color:var(--cms-text-muted)">Super Admin role is protected.</p>
                        @else
                            <select id="rbac_role_id" name="rbac_role_id" required>
                                <option value="">Select a role…</option>
                                @foreach ($assignableRoles as $option)
                                    <option value="{{ $option['id'] }}" @selected($selectedRoleId === (int) $option['id'])>
                                        {{ $option['name'] }}
                                        @if (! empty($option['dashboard_type']))
                                            — {{ ucwords(str_replace('_', ' ', $option['dashboard_type'])) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <p style="margin:6px 0 0;font-size:0.8rem;color:var(--cms-text-muted)">
                                All project roles are listed. Extra roles can still be added under Roles &amp; Permissions.
                            </p>
                        @endif
                    </div>
                    <div class="cms-field">
                        <label for="account_status">Account status</label>
                        <select id="account_status" name="account_status" required @disabled($isEdit && ! empty($managedAdmin['is_super_admin']))>
                            @foreach ($accountStatuses as $option)
                                <option value="{{ $option['value'] }}" @selected(old('account_status', $managedAdmin['account_status'] ?? 'active') === $option['value'])>
                                    {{ $option['label'] }}
                                </option>
                            @endforeach
                        </select>
                        @if ($isEdit && ! empty($managedAdmin['is_super_admin']))
                            <input type="hidden" name="account_status" value="active">
                        @endif
                    </div>
                    <div class="cms-field">
                        <label for="recovery_email">Recovery email</label>
                        <input id="recovery_email" type="email" name="recovery_email" maxlength="191" value="{{ old('recovery_email', $managedAdmin['recovery_email'] ?? '') }}">
                    </div>
                    <div class="cms-field">
                        <label for="recovery_phone">Recovery phone</label>
                        <input id="recovery_phone" name="recovery_phone" maxlength="30" value="{{ old('recovery_phone', $managedAdmin['recovery_phone'] ?? '') }}">
                    </div>
                    <div class="cms-field">
                        <label for="password">{{ $isEdit ? 'New password (optional)' : 'Password' }}</label>
                        <input id="password" type="password" name="password" minlength="8" maxlength="191" @required(! $isEdit) autocomplete="new-password">
                    </div>
                </div>

                <div style="margin-top:16px;display:flex;flex-wrap:wrap;gap:16px;align-items:center">
                    <label style="display:inline-flex;gap:8px;align-items:center">
                        <input type="hidden" name="force_password_change" value="0">
                        <input type="checkbox" name="force_password_change" value="1" @checked(old('force_password_change', $managedAdmin['force_password_change'] ?? false))>
                        Require password change on next login
                    </label>
                    @if ($isEdit && ! empty($managedAdmin['locked_at']))
                        <label style="display:inline-flex;gap:8px;align-items:center">
                            <input type="checkbox" name="unlock" value="1">
                            Unlock this account
                        </label>
                    @endif
                </div>

                @if ($isEdit && ! empty($managedAdmin['role_names']))
                    <p style="margin-top:16px;color:var(--cms-text-muted);font-size:0.9rem">
                        RBAC roles:
                        {{ implode(', ', $managedAdmin['role_names']) }}.
                        <a href="{{ route('settings.admins.permissions', $managedAdmin['id']) }}" style="color:var(--cms-gold,#c9a227)">View permissions</a>
                        ·
                        <a href="{{ route('settings.index', ['tab' => 'roles', 'assign_admin' => $managedAdmin['id']]) }}" style="color:var(--cms-gold,#c9a227)">Change assignments</a>
                    </p>
                @elseif ($isEdit)
                    <p style="margin-top:16px;color:var(--cms-text-muted);font-size:0.9rem">
                        No RBAC roles assigned yet.
                        <a href="{{ route('settings.admins.permissions', $managedAdmin['id']) }}" style="color:var(--cms-gold,#c9a227)">View permissions</a>
                        ·
                        <a href="{{ route('settings.index', ['tab' => 'roles', 'assign_admin' => $managedAdmin['id']]) }}" style="color:var(--cms-gold,#c9a227)">Assign roles</a>
                    </p>
                @endif

                <div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap">
                    <button type="submit" class="cms-btn cms-btn--primary">{{ $isEdit ? 'Save changes' : 'Create administrator' }}</button>
                    <a href="{{ route('settings.index', ['tab' => 'admins']) }}" class="cms-btn cms-btn--ghost">Cancel</a>
                </div>
            </form>
        </div>
    </article>
</x-app-layout>
