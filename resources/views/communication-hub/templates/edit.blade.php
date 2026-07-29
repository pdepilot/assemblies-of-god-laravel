<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Edit Template</h2></x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('communication-hub._nav', ['canManage' => true])
            <form method="POST" action="{{ route('communication-hub.templates.update', $template['id']) }}" class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-sm font-medium">Name</label>
                    <input name="name" value="{{ old('name', $template['name']) }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900" required>
                </div>
                <div>
                    <label class="block text-sm font-medium">Channel</label>
                    <select name="channel" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                        @foreach ($channels as $c)
                            <option value="{{ $c }}" @selected(old('channel', $template['channel']) === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium">Subject</label>
                    <input name="subject" value="{{ old('subject', $template['subject'] ?? '') }}" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                </div>
                <div>
                    <label class="block text-sm font-medium">Message</label>
                    <textarea name="body_text" rows="12" class="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ old('body_text', $template['display_body'] ?? '') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Plain text only. Use <code>@{{member_name}}</code> or <code>@{{donor_name}}</code> where the person's name should appear.</p>
                </div>
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium">Update</button>
            </form>
        </div>
    </div>
</x-app-layout>
