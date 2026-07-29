@php
    $content = $content ?? [];
    $items = array_values($content['items'] ?? []);
    for ($i = 0; $i < 2; $i++) {
        $items[] = '';
    }
@endphp

<div class="space-y-3">
    <h4 class="font-semibold text-sm text-gray-700 dark:text-gray-200">Live Giving Feed Messages</h4>
    @foreach ($items as $i => $text)
        <div>
            <input name="content[items][{{ $i }}]" value="{{ old("content.items.$i", $text) }}" class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-900 text-sm" placeholder="Anonymous donor from Canada supported Media Ministry.">
        </div>
    @endforeach
    <p class="text-xs text-gray-400">Blank rows are discarded automatically when you save.</p>
</div>
