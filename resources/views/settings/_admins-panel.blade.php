{{-- Administrators management panel (super admin) --}}
<article class="cms-card">
    <div class="cms-card__head" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px">
        <h2 class="cms-card__title" style="margin:0">Administrators</h2>
        @if ($canManageRbac)
            <a href="{{ route('settings.admins.create') }}" class="cms-btn cms-btn--primary cms-btn--sm">
                <i class="fas fa-user-plus" aria-hidden="true"></i> Add admin
            </a>
        @endif
    </div>
    <div class="cms-card__body">
        <p style="color:var(--cms-text-muted);margin:0 0 16px">
            Create, edit, or delete administrator accounts. Assign module roles under
            <a href="{{ route('settings.index', ['tab' => 'roles']) }}" style="color:var(--cms-gold,#c9a227)">Roles &amp; Permissions</a>.
        </p>

        @if ($canManageRbac)
            <form method="GET" action="{{ route('settings.index') }}" style="margin-bottom:16px;display:flex;gap:10px;flex-wrap:wrap">
                <input type="hidden" name="tab" value="admins">
                <div class="cms-field" style="flex:1;min-width:220px">
                    <label for="admin_q">Search</label>
                    <input id="admin_q" type="search" name="q" value="{{ $adminsQuery ?? '' }}" placeholder="Name, email, username…">
                </div>
                <div style="display:flex;align-items:end">
                    <button type="submit" class="cms-btn cms-btn--ghost">Search</button>
                </div>
            </form>

            <div class="cms-table-wrap">
                <table class="cms-table">
                    <thead>
                        <tr>
                            <th>Administrator</th>
                            <th>Account role</th>
                            <th>RBAC roles</th>
                            <th>Status</th>
                            <th>Last login</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($managedAdmins ?? []) as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['full_name'] }}</strong>
                                    <div style="font-size:0.8rem;color:var(--cms-text-muted)">{{ $row['email'] }}</div>
                                    @if (! empty($row['username']))
                                        <div style="font-size:0.75rem;color:var(--cms-text-muted);font-family:monospace">{{ '@'.$row['username'] }}</div>
                                    @endif
                                </td>
                                <td>{{ str_replace('_', ' ', $row['role']) }}</td>
                                <td>
                                    @if (! empty($row['role_names']))
                                        <div style="display:flex;flex-wrap:gap:4px">
                                            @foreach ($row['role_names'] as $roleName)
                                                <span class="cms-badge" style="background:rgba(201,162,39,.15);color:var(--cms-gold,#c9a227);padding:2px 8px;border-radius:999px;font-size:0.72rem">{{ $roleName }}</span>
                                            @endforeach
                                        </div>
                                    @else
                                        <span style="color:var(--cms-text-muted);font-size:0.85rem">None</span>
                                    @endif
                                </td>
                                <td>
                                    @if (! empty($row['is_active']) && ($row['account_status'] ?? '') === 'active')
                                        <span style="color:#34d399">Active</span>
                                    @else
                                        <span style="color:#f87171">{{ ucfirst((string) ($row['account_status'] ?? 'inactive')) }}</span>
                                    @endif
                                    @if (! empty($row['is_super_admin']))
                                        <div style="font-size:0.75rem;color:var(--cms-text-muted)">Protected</div>
                                    @endif
                                </td>
                                <td style="font-size:0.85rem;color:var(--cms-text-muted)">
                                    {{ ! empty($row['last_login_at']) ? \Illuminate\Support\Carbon::parse($row['last_login_at'])->diffForHumans() : 'Never' }}
                                </td>
                                <td style="white-space:nowrap">
                                    <a href="{{ route('settings.admins.permissions', $row['id']) }}" class="cms-btn cms-btn--ghost cms-btn--sm">Permissions</a>
                                    <a href="{{ route('settings.admins.edit', $row['id']) }}" class="cms-btn cms-btn--ghost cms-btn--sm">Edit</a>
                                    @unless (! empty($row['is_super_admin']) || (int) $row['id'] === (int) $admin->id)
                                        @if (($row['account_status'] ?? '') === 'suspended' || empty($row['is_active']))
                                            <form method="POST" action="{{ route('settings.admins.status', $row['id']) }}" style="display:inline">
                                                @csrf
                                                <input type="hidden" name="account_status" value="active">
                                                <button type="submit" class="cms-btn cms-btn--ghost cms-btn--sm" style="color:#34d399">Activate</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('settings.admins.status', $row['id']) }}" style="display:inline" data-confirm="Suspend {{ $row['full_name'] }}? They will not be able to sign in." data-confirm-title="Suspend administrator" data-confirm-ok="Suspend" data-confirm-tone="danger">
                                                @csrf
                                                <input type="hidden" name="account_status" value="suspended">
                                                <button type="submit" class="cms-btn cms-btn--ghost cms-btn--sm" style="color:#fbbf24">Suspend</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('settings.admins.destroy', $row['id']) }}" style="display:inline" data-confirm="Delete {{ $row['full_name'] }}? This cannot be undone." data-confirm-title="Delete administrator" data-confirm-ok="Delete" data-confirm-tone="danger">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="cms-btn cms-btn--ghost cms-btn--sm" style="color:#f87171">Delete</button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="cms-table__empty">No administrators found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            <p style="color:var(--cms-text-muted);margin:0">Super Administrator access is required to manage administrators.</p>
        @endif
    </div>
</article>
