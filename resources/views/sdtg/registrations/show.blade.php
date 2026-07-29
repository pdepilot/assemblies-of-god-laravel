<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Registration {{ $registration['registration_code'] ?? '' }}</h2></x-slot>
    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))<div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>@endif
            @include('sdtg._nav', ['canManage' => $canManage])
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-2">
                <p><strong>Name:</strong> {{ $registration['full_name'] }}</p>
                <p><strong>Email:</strong> {{ $registration['email'] ?? '—' }}</p>
                <p><strong>Status:</strong> {{ $registration['status'] }}</p>
                @if ($canManage)
                <form method="POST" action="{{ route('sdtg.registrations.update', $registration['id']) }}" class="pt-4 space-y-3">
                    @csrf @method('PUT')
                    <div><label class="block text-sm font-medium">Status</label><select name="status" class="mt-1 rounded border-gray-300">@foreach($statuses as $s)<option value="{{ $s }}" @selected($registration['status']===$s)>{{ $s }}</option>@endforeach</select></div>
                    <div><label class="block text-sm font-medium">Notes</label><textarea name="notes" rows="3" class="mt-1 w-full rounded border-gray-300">{{ $registration['notes'] ?? '' }}</textarea></div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Update Registration</button>
                </form>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
