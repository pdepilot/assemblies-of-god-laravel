<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Team Member</h2></x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('website.team.update', $member['id']) }}" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium">Full name</label>
                    <input name="full_name" value="{{ old('full_name', $member['full_name'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                </div>
                <div>
                    <label class="block text-sm font-medium">Role title</label>
                    <input name="role_title" value="{{ old('role_title', $member['role_title'] ?? '') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm font-medium">Member type</label>
                    <input name="member_type" value="{{ old('member_type', $member['member_type'] ?? 'member') }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm font-medium">Bio</label>
                    <textarea name="bio" rows="4" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('bio', $member['bio'] ?? '') }}</textarea>
                </div>
                <div class="space-y-3">
                    <label class="block text-sm font-medium" for="photo">Photo</label>
                    @if (! empty($member['photo_url']))
                        <img src="{{ $member['photo_url'] }}" alt="{{ $member['full_name'] ?? 'Team member' }}" class="h-24 w-24 rounded object-cover border">
                        <label class="inline-flex items-center gap-2 text-sm text-red-700 dark:text-red-300">
                            <input type="checkbox" name="remove_photo" value="1" @checked(old('remove_photo')) class="rounded border-gray-300">
                            Remove current photo
                        </label>
                    @endif
                    <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" class="mt-1 w-full text-sm">
                    <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Upload only — no URL.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium">Sort order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $member['sort_order'] ?? 0) }}" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    </div>
                    <div class="flex items-end pb-2">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', !empty($member['is_active']))) class="rounded border-gray-300">
                            Active
                        </label>
                    </div>
                </div>
                <div class="flex gap-3">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Update member</button>
                    <a href="{{ route('website.team.index') }}" class="px-4 py-2 border rounded-md text-sm">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
