<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl">Edit Vendor</h2></x-slot>
    <div class="py-10"><div class="max-w-3xl mx-auto sm:px-6 lg:px-8"><div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('financial-erp.vendors.update', $vendor) }}" class="space-y-4">@csrf @method('PUT')
            <div><label class="block text-sm">Name *</label><input name="name" value="{{ old('name', $vendor->name) }}" required class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
            <div><label class="block text-sm">Phone</label><input name="phone" value="{{ old('phone', $vendor->phone) }}" class="mt-1 w-full rounded-md border-gray-300 dark:bg-gray-900"></div>
            <button class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold">Update</button>
        </form>
    </div></div></div>
</x-app-layout>
