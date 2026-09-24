<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;

class SaveWebsitePageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'heading' => ['nullable', 'string', 'max:255'],
            'eyebrow' => ['nullable', 'string', 'max:120'],
            'intro' => ['nullable', 'string', 'max:2000'],
            'body_html' => ['nullable', 'string'],
            'cta_label' => ['nullable', 'string', 'max:120'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'hero_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_hero_image' => ['nullable', 'boolean'],
            'ministries_eyebrow' => ['nullable', 'string', 'max:120'],
            'ministries_title' => ['nullable', 'string', 'max:255'],
            'events_eyebrow' => ['nullable', 'string', 'max:120'],
            'events_title' => ['nullable', 'string', 'max:255'],
            'events_intro' => ['nullable', 'string', 'max:2000'],
            'worship_eyebrow' => ['nullable', 'string', 'max:120'],
            'worship_title' => ['nullable', 'string', 'max:255'],
            'worship_intro' => ['nullable', 'string', 'max:2000'],
            'sermons_eyebrow' => ['nullable', 'string', 'max:120'],
            'sermons_title' => ['nullable', 'string', 'max:255'],
            'team_eyebrow' => ['nullable', 'string', 'max:120'],
            'team_title' => ['nullable', 'string', 'max:255'],
            'testimonials_eyebrow' => ['nullable', 'string', 'max:120'],
            'testimonials_title' => ['nullable', 'string', 'max:255'],
            'testimonials_intro' => ['nullable', 'string', 'max:2000'],
            'hero_badge' => ['nullable', 'string', 'max:120'],
            'hero_title' => ['nullable', 'string', 'max:500'],
            'hero_scripture' => ['nullable', 'string', 'max:1000'],
            'hero_ref' => ['nullable', 'string', 'max:120'],
            'hero_cta_label' => ['nullable', 'string', 'max:120'],
            'categories_eyebrow' => ['nullable', 'string', 'max:120'],
            'categories_title' => ['nullable', 'string', 'max:255'],
            'categories_lead' => ['nullable', 'string', 'max:2000'],
            'online_eyebrow' => ['nullable', 'string', 'max:120'],
            'online_title' => ['nullable', 'string', 'max:255'],
            'online_lead' => ['nullable', 'string', 'max:2000'],
            'pledge_eyebrow' => ['nullable', 'string', 'max:120'],
            'pledge_title' => ['nullable', 'string', 'max:255'],
            'pledge_lead' => ['nullable', 'string', 'max:2000'],
            'sponsorship_eyebrow' => ['nullable', 'string', 'max:120'],
            'sponsorship_title' => ['nullable', 'string', 'max:255'],
            'sponsorship_lead' => ['nullable', 'string', 'max:2000'],
            'trust_eyebrow' => ['nullable', 'string', 'max:120'],
            'trust_title' => ['nullable', 'string', 'max:255'],
            'impact_eyebrow' => ['nullable', 'string', 'max:120'],
            'impact_title' => ['nullable', 'string', 'max:255'],
            'impact_lead' => ['nullable', 'string', 'max:2000'],
            'donors_eyebrow' => ['nullable', 'string', 'max:120'],
            'donors_title' => ['nullable', 'string', 'max:255'],
            'donors_lead' => ['nullable', 'string', 'max:2000'],
            'stories_eyebrow' => ['nullable', 'string', 'max:120'],
            'stories_title' => ['nullable', 'string', 'max:255'],
            'stories_lead' => ['nullable', 'string', 'max:2000'],
            'final_cta_title' => ['nullable', 'string', 'max:255'],
            'final_cta_text' => ['nullable', 'string', 'max:2000'],
            'final_cta_label' => ['nullable', 'string', 'max:120'],
            // SEO (saved alongside page content)
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_meta_description' => ['nullable', 'string', 'max:500'],
            'seo_og_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_seo_og_image' => ['nullable', 'boolean'],
            'programs' => ['nullable', 'array', 'max:6'],
            'programs.*.day' => ['nullable', 'string', 'max:120'],
            'programs.*.icon' => ['nullable', 'string', 'max:40'],
            'programs.*.title' => ['nullable', 'string', 'max:255'],
            'programs.*.time_primary' => ['nullable', 'string', 'max:80'],
            'programs.*.note_primary' => ['nullable', 'string', 'max:255'],
            'programs.*.time_secondary' => ['nullable', 'string', 'max:80'],
            'programs.*.note_secondary' => ['nullable', 'string', 'max:255'],
            'programs.*.body' => ['nullable', 'string', 'max:2000'],
            'programs.*.cta_label' => ['nullable', 'string', 'max:80'],
            'programs.*.cta_url' => ['nullable', 'string', 'max:500'],
            'worship_map_query' => ['nullable', 'string', 'max:255'],
            'activities' => ['nullable', 'array', 'max:8'],
            'activities.*.id' => ['nullable', 'integer', 'min:0'],
            'activities.*.title' => ['nullable', 'string', 'max:255'],
            'activities.*.description' => ['nullable', 'string', 'max:2000'],
            'activities.*.icon_class' => ['nullable', 'string', 'max:50'],
            'activities.*.meeting_schedule' => ['nullable', 'string', 'max:255'],
            'activities.*.read_more_url' => ['nullable', 'string', 'max:500'],
            'activities.*.is_published' => ['nullable'],
        ];
    }
}
