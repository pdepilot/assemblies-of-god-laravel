<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">AI Message Assistant</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Architecture ready — no AI provider connected yet. Jobs are queued for future processing.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @include('communication-hub._nav', ['canManage' => $canManage])

            @if (session('status'))
                <div class="rounded-md bg-green-50 dark:bg-green-900/30 p-4 text-sm text-green-800 dark:text-green-200">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 space-y-5">
                <form method="POST" action="{{ route('communication-hub.ai-assistant.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label for="job_type" class="block text-sm font-medium mb-1">Capability</label>
                        <select id="job_type" name="job_type" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900">
                            @foreach ($capabilities as $option)
                                <option value="{{ $option['value'] }}" @selected(old('job_type', 'birthday') === $option['value'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="prompt" class="block text-sm font-medium mb-1">Prompt / Draft</label>
                        <textarea id="prompt" name="prompt" rows="8" required placeholder="Describe what you need…" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-900">{{ old('prompt') }}</textarea>
                    </div>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">Queue AI Job</button>
                </form>

                @if (! empty($jobResult))
                    <div class="rounded-md border border-indigo-200 dark:border-indigo-800 bg-indigo-50 dark:bg-indigo-900/20 p-4 text-sm space-y-3">
                        <p>{{ $jobResult['message'] }}</p>
                        <p class="text-xs text-gray-500">Job #{{ $jobResult['id'] }} · {{ $jobResult['status'] }}</p>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach ($jobResult['capabilities'] as $capability)
                                <li>{{ $capability }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
