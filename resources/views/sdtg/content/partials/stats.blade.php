@php $content = $content ?? []; @endphp

@include('sdtg.content.partials._stat_items', [
    'items' => $content['items'] ?? [],
    'name' => 'items',
    'label' => 'Statistic Counters',
    'withIcon' => true,
    'spares' => 2,
])
