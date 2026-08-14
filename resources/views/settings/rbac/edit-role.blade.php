@php
    $isEdit = is_array($role ?? null);
    $selected = collect(old('permission_ids', $selectedPermissionIds ?? []))->map(fn ($id) => (int) $id)->all();
    $backHref = $backRoute ?? route('settings.index', ['tab' => 'roles']);
    $storeHref = $formStoreRoute ?? route('settings.rbac.roles.store');
    $updateHref = $formUpdateRoute ?? ($isEdit ? route('settings.rbac.roles.update', $role['id']) : $storeHref);
    $fixedPlatform = $fixedPlatform ?? \App\Support\RbacPlatform::AG;
    $platformValue = old('platform', $role['platform'] ?? $fixedPlatform);
    $layoutComponent = $shellLayout ?? 'app-layout';
@endphp

<x-dynamic-component :component="$layoutComponent">
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    {{ $isEdit ? 'Edit role' : 'Create role' }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Choose a dashboard, then tick the permissions this role should grant.
                </p>
            </div>
            <a href="{{ $backHref }}" class="px-4 py-2 text-sm rounded-md border">Back to Roles</a>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ $errors->first() }}</div>
            @endif

            <form
                method="POST"
                action="{{ $isEdit ? $updateHref : $storeHref }}"
                class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-6"
                id="rbacRoleForm"
            >
                @csrf
                @if ($isEdit) @method('PUT') @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium" for="name">Role name</label>
                        <input id="name" name="name" required maxlength="120" value="{{ old('name', $role['name'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="slug">Slug</label>
                        <input
                            id="slug"
                            name="slug"
                            maxlength="64"
                            value="{{ old('slug', $role['slug'] ?? '') }}"
                            @disabled($isEdit && ($role['slug'] ?? '') === 'super_admin')
                            class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 font-mono text-sm"
                            placeholder="auto-from-name"
                        >
                        @if ($isEdit && ($role['slug'] ?? '') === 'super_admin')
                            <input type="hidden" name="slug" value="super_admin">
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="dashboard_type">Dashboard</label>
                        <select id="dashboard_type" name="dashboard_type" required class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                            @foreach ($dashboardTypes as $type)
                                <option value="{{ $type['value'] }}" @selected(old('dashboard_type', $role['dashboard_type'] ?? 'church_admin') === $type['value'])>
                                    {{ $type['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" for="platform">Platform</label>
                        @if (($role['slug'] ?? '') === 'super_admin')
                            <input type="hidden" name="platform" value="both">
                            <div class="mt-1 rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900">
                                both (required)
                            </div>
                        @else
                            <input type="hidden" name="platform" value="ag">
                            <div class="mt-1 rounded-md border border-gray-300 dark:border-gray-700 px-3 py-2 text-sm bg-gray-50 dark:bg-gray-900">
                                AG
                            </div>
                        @endif
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $role['is_active'] ?? true)) class="rounded border-gray-300">
                            Role is active
                        </label>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium" for="description">Description</label>
                        <textarea id="description" name="description" rows="2" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('description', $role['description'] ?? '') }}</textarea>
                    </div>
                </div>

                @if (($role['slug'] ?? '') === 'super_admin')
                    <div class="rounded-md bg-amber-50 dark:bg-amber-900/20 p-4 text-sm text-amber-800 dark:text-amber-200">
                        Super Admin always has full access. Permission checkboxes below are informational and can still be stored for documentation.
                    </div>
                @endif

                <div class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="font-semibold">Permissions</h3>
                        <div class="flex gap-2 text-sm">
                            <button type="button" class="px-3 py-1 rounded border" id="rbacSelectAll">Select all</button>
                            <button type="button" class="px-3 py-1 rounded border" id="rbacClearAll">Clear all</button>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500">Open a module, use “Select module” for everything in that group, or tick individual actions.</p>

                    <div class="space-y-3">
                        @foreach ($permissionModules as $module)
                            @php
                                $moduleIds = collect($module['permissions'])->pluck('id')->map(fn ($id) => (int) $id)->all();
                                $selectedInModule = count(array_intersect($moduleIds, $selected));
                                $open = $selectedInModule > 0;
                            @endphp
                            <details class="rounded-lg border border-gray-200 dark:border-gray-700" @if ($open) open @endif data-module>
                                <summary class="cursor-pointer px-4 py-3 flex flex-wrap items-center justify-between gap-2 bg-gray-50 dark:bg-gray-900/40">
                                    <span class="font-medium">{{ $module['label'] }}</span>
                                    <span class="text-xs text-gray-500">
                                        <span data-module-count>{{ $selectedInModule }}</span>/{{ count($module['permissions']) }} selected
                                    </span>
                                </summary>
                                <div class="p-4 space-y-3">
                                    <button type="button" class="text-sm text-indigo-600 hover:underline" data-select-module>
                                        Select all in {{ $module['label'] }}
                                    </button>
                                    <div class="grid gap-2 sm:grid-cols-2">
                                        @foreach ($module['permissions'] as $permission)
                                            <label class="flex gap-2 items-start text-sm">
                                                <input
                                                    type="checkbox"
                                                    name="permission_ids[]"
                                                    value="{{ $permission['id'] }}"
                                                    class="mt-0.5 rounded border-gray-300 rbac-permission"
                                                    data-module-key="{{ $module['key'] }}"
                                                    @checked(in_array((int) $permission['id'], $selected, true))
                                                >
                                                <span>
                                                    <span class="font-medium">{{ $permission['label'] }}</span>
                                                    <span class="block text-xs text-gray-500 font-mono">{{ $permission['permission_key'] }}</span>
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">
                        {{ $isEdit ? 'Save role' : 'Create role' }}
                    </button>
                    <a href="{{ $backHref }}" class="px-4 py-2 rounded-md border text-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            function refreshCounts() {
                document.querySelectorAll('[data-module]').forEach(function (block) {
                    var boxes = block.querySelectorAll('.rbac-permission');
                    var checked = block.querySelectorAll('.rbac-permission:checked').length;
                    var counter = block.querySelector('[data-module-count]');
                    if (counter) counter.textContent = String(checked);
                });
            }

            document.getElementById('rbacSelectAll')?.addEventListener('click', function () {
                document.querySelectorAll('.rbac-permission').forEach(function (el) { el.checked = true; });
                refreshCounts();
            });
            document.getElementById('rbacClearAll')?.addEventListener('click', function () {
                document.querySelectorAll('.rbac-permission').forEach(function (el) { el.checked = false; });
                refreshCounts();
            });
            document.querySelectorAll('[data-select-module]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var block = btn.closest('[data-module]');
                    if (!block) return;
                    block.querySelectorAll('.rbac-permission').forEach(function (el) { el.checked = true; });
                    refreshCounts();
                });
            });
            document.querySelectorAll('.rbac-permission').forEach(function (el) {
                el.addEventListener('change', refreshCounts);
            });
        })();
    </script>
</x-dynamic-component>
