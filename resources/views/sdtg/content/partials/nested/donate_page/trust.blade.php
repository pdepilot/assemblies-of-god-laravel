@php $content = $content ?? []; @endphp

@include('sdtg.content.partials._icon_cards', [
    'items' => $content['items'] ?? [],
    'name' => 'items',
    'label' => 'Trust Badges',
    'withProgress' => false,
    'spares' => 1,
])
