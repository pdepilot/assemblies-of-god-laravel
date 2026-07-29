<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">New Project</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('financial-erp.projects.store') }}" class="space-y-4">@csrf
            <div><label class="block text-sm">Name *</label><input name="name" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">@error('name')<p class="text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div class="grid gap-4 sm:grid-cols-2"><div><label class="block text-sm">Type</label><select name="project_type" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900">@foreach($types as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach</select></div><div><label class="block text-sm">Budget</label><input type="number" step="0.01" name="budget_amount" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div></div>
            <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Create</button>
        </form>
    </div></div></div>
</x-app-layout>
