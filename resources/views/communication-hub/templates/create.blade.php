<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">New Template</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <form method="POST" action="{{ route('communication-hub.templates.store') }}" class="bg-white shadow-sm sm:rounded-lg p-6 space-y-4">
            @csrf
            <div><label class="block text-sm font-medium">Name</label><input name="name" value="{{ old('name') }}" class="mt-1 w-full rounded border-gray-300" required></div>
            <div><label class="block text-sm font-medium">Channel</label><select name="channel" class="mt-1 w-full rounded border-gray-300">@foreach($channels as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select></div>
            <div><label class="block text-sm font-medium">Category</label><select name="category_slug" class="mt-1 w-full rounded border-gray-300">@foreach($categories as $cat)<option value="{{ $cat['slug'] }}">{{ $cat['name'] }}</option>@endforeach</select></div>
            <div><label class="block text-sm font-medium">Subject</label><input name="subject" value="{{ old('subject') }}" class="mt-1 w-full rounded border-gray-300"></div>
            <div>
                <label class="block text-sm font-medium">Message</label>
                <textarea name="body_text" rows="12" class="mt-1 w-full rounded border-gray-300">{{ old('body_text') }}</textarea>
                <p class="mt-1 text-xs text-gray-500">Plain text only. Use <code>@{{member_name}}</code> where the person's name should appear.</p>
            </div>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Save Template</button>
        </form>
    </div></div>
</x-app-layout>
