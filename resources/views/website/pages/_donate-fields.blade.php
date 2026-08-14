@php
    $field = function (string $name, string $label, string $type = 'text', int $rows = 3) use ($page): void {
        $value = old($name, $page[$name] ?? '');
        echo '<div>';
        echo '<label class="block text-sm font-medium">'.e($label).'</label>';
        if ($type === 'textarea') {
            echo '<textarea name="'.e($name).'" rows="'.$rows.'" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">'.e($value).'</textarea>';
        } else {
            echo '<input name="'.e($name).'" value="'.e($value).'" class="mt-1 w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900">';
        }
        echo '</div>';
    };
@endphp

<h3 class="font-semibold">Hero</h3>
@php $field('hero_badge', 'Badge'); @endphp
@php $field('hero_title', 'Title', 'textarea', 2); @endphp
@php $field('hero_scripture', 'Scripture', 'textarea', 3); @endphp
@php $field('hero_ref', 'Scripture reference'); @endphp
@php $field('hero_cta_label', 'CTA label'); @endphp

<h3 class="font-semibold pt-2">Categories section</h3>
@php $field('categories_eyebrow', 'Eyebrow'); @endphp
@php $field('categories_title', 'Title'); @endphp
@php $field('categories_lead', 'Lead', 'textarea'); @endphp

<h3 class="font-semibold pt-2">Online giving section</h3>
@php $field('online_eyebrow', 'Eyebrow'); @endphp
@php $field('online_title', 'Title'); @endphp
@php $field('online_lead', 'Lead', 'textarea'); @endphp

<h3 class="font-semibold pt-2">Pledge section</h3>
@php $field('pledge_eyebrow', 'Eyebrow'); @endphp
@php $field('pledge_title', 'Title'); @endphp
@php $field('pledge_lead', 'Lead', 'textarea'); @endphp

<h3 class="font-semibold pt-2">Sponsorship section</h3>
@php $field('sponsorship_eyebrow', 'Eyebrow'); @endphp
@php $field('sponsorship_title', 'Title'); @endphp
@php $field('sponsorship_lead', 'Lead', 'textarea'); @endphp

<h3 class="font-semibold pt-2">Trust section</h3>
@php $field('trust_eyebrow', 'Eyebrow'); @endphp
@php $field('trust_title', 'Title'); @endphp

<h3 class="font-semibold pt-2">Impact section</h3>
@php $field('impact_eyebrow', 'Eyebrow'); @endphp
@php $field('impact_title', 'Title'); @endphp
@php $field('impact_lead', 'Lead', 'textarea'); @endphp

<h3 class="font-semibold pt-2">Recent donors section</h3>
@php $field('donors_eyebrow', 'Eyebrow'); @endphp
@php $field('donors_title', 'Title'); @endphp
@php $field('donors_lead', 'Lead', 'textarea'); @endphp

<h3 class="font-semibold pt-2">Giver stories section</h3>
@php $field('stories_eyebrow', 'Eyebrow'); @endphp
@php $field('stories_title', 'Title'); @endphp
@php $field('stories_lead', 'Lead', 'textarea'); @endphp

<h3 class="font-semibold pt-2">Final CTA</h3>
@php $field('final_cta_title', 'Title'); @endphp
@php $field('final_cta_text', 'Text', 'textarea'); @endphp
@php $field('final_cta_label', 'Button label'); @endphp
