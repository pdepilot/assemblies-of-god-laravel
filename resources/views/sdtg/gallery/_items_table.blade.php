<div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 overflow-x-auto">
    <div class="flex justify-between text-xs text-gray-500 mb-3">
        <h3 class="font-semibold text-sm text-gray-800 dark:text-gray-200">Items in this section</h3>
        <span>Showing {{ count($result['items']) }} of {{ $result['total'] }}</span>
    </div>
    <table class="min-w-full text-sm">
        <thead>
            <tr class="text-left border-b">
                <th class="py-2 w-20">Preview</th>
                <th class="py-2">Title</th>
                <th>Type</th>
                <th>Category</th>
                <th>Album</th>
                <th>Year</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse ($result['items'] as $item)
            <tr class="border-b">
                <td class="py-2">
                    @if (! empty($item['thumbnail_url'] ?? $item['image_url']))
                        <img src="{{ $item['thumbnail_url'] ?? $item['image_url'] }}" alt="" class="h-12 w-16 rounded object-cover bg-gray-100">
                    @else
                        <span class="inline-flex h-12 w-16 items-center justify-center rounded bg-gray-100 text-[10px] text-gray-400">No media</span>
                    @endif
                </td>
                <td class="py-2 font-medium">
                    {{ $item['title'] }}
                    @if (! empty($item['is_featured']))
                        <span class="ml-1 text-xs text-indigo-600">Featured</span>
                    @endif
                    @if (! empty($item['is_speakers_highlight']))
                        <span class="ml-1 text-xs text-amber-600">Speakers</span>
                    @endif
                </td>
                <td class="capitalize">{{ $item['media_type'] }}</td>
                <td>{{ $item['category'] }}</td>
                <td>{{ $item['album_title'] ?? '—' }}</td>
                <td>{{ $item['crusade_year'] }}</td>
                <td>{{ ! empty($item['is_published']) ? 'Published' : 'Draft' }}</td>
                <td class="whitespace-nowrap space-x-3">
                    @if ($canManage)
                        <a href="{{ route('sdtg.gallery.items.edit', $item['id']) }}" class="text-indigo-600">Edit</a>
                        <form method="POST" action="{{ route('sdtg.gallery.items.destroy', $item['id']) }}" class="inline" data-confirm="Delete this item?" data-confirm-title="Please confirm" data-confirm-ok="Confirm" data-confirm-tone="danger">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600">Delete</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="py-6 text-center text-gray-500">{{ $emptyMessage }}</td></tr>
        @endforelse
        </tbody>
    </table>

    @if ($result['pages'] > 1)
        <div class="mt-4 flex gap-2 text-sm">
            @if ($result['page'] > 1)
                <a class="px-3 py-1 rounded border" href="{{ route('sdtg.gallery.index', array_merge($filters, ['tab' => $tab, 'page' => $result['page'] - 1])) }}">Previous</a>
            @endif
            @if ($result['page'] < $result['pages'])
                <a class="px-3 py-1 rounded border" href="{{ route('sdtg.gallery.index', array_merge($filters, ['tab' => $tab, 'page' => $result['page'] + 1])) }}">Next</a>
            @endif
        </div>
    @endif
</div>
