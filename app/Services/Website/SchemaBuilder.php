<?php

namespace App\Services\Website;

final class SchemaBuilder
{
    /** @return list<array<string, mixed>> */
    public function organizationAndChurch(): array
    {
        $identity = config('identity.public', []);
        $name = (string) ($identity['site_name'] ?? 'AGC Ikenegbu Assemblies of God');
        $short = (string) ($identity['short_name'] ?? 'AGC Ikenegbu');
        $logo = asset('site/'.ltrim((string) ($identity['logo_path'] ?? 'images/ag-logo.jpeg'), '/'));
        $url = url('/');

        $organization = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'alternateName' => $short,
            'url' => $url,
            'logo' => $logo,
            'sameAs' => array_values(array_filter([
                config('identity.public.facebook_url'),
                config('identity.public.youtube_url'),
            ])),
        ];

        $church = [
            '@context' => 'https://schema.org',
            '@type' => 'Church',
            'name' => $name,
            'url' => $url,
            'image' => $logo,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => 'Owerri',
                'addressRegion' => 'Imo State',
                'addressCountry' => 'NG',
                'streetAddress' => (string) config('identity.email.church_address', 'Ikenegbu Layout, Owerri'),
            ],
            'telephone' => (string) config('identity.email.church_phone', ''),
        ];

        $website = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $short,
            'url' => $url,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => url('/blog').'?q={search_term_string}',
                ],
                'query-input' => 'required name=search_term_string',
            ],
        ];

        return [$organization, $church, $website];
    }

    /**
     * @param  list<array{name: string, url: string}>  $crumbs
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $crumbs): array
    {
        $items = [];
        foreach (array_values($crumbs) as $i => $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $crumb['name'],
                'item' => $crumb['url'],
            ];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    /**
     * @param  array<string, mixed>  $post
     * @return array<string, mixed>
     */
    public function article(array $post, string $url, string $imageUrl): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => (string) ($post['title'] ?? ''),
            'description' => (string) ($post['meta_description'] ?? $post['excerpt'] ?? ''),
            'image' => $imageUrl !== '' ? [$imageUrl] : [],
            'datePublished' => ! empty($post['published_at']) ? date('c', strtotime((string) $post['published_at'])) : null,
            'dateModified' => ! empty($post['updated_at']) ? date('c', strtotime((string) $post['updated_at'])) : null,
            'author' => [
                '@type' => 'Person',
                'name' => (string) ($post['author'] ?? config('identity.public.short_name', 'AGC Ikenegbu')),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => (string) config('identity.public.site_name', 'AGC Ikenegbu Assemblies of God'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('site/'.ltrim((string) config('identity.public.logo_path', 'images/ag-logo.jpeg'), '/')),
                ],
            ],
            'mainEntityOfPage' => $url,
            'url' => $url,
        ];
    }

    /**
     * @param  array<string, mixed>  $sermon
     * @return array<string, mixed>
     */
    public function sermon(array $sermon, string $url, string $imageUrl): array
    {
        $graph = [
            '@context' => 'https://schema.org',
            '@type' => 'CreativeWork',
            'name' => (string) ($sermon['title'] ?? ''),
            'description' => (string) ($sermon['seo_description'] ?? $sermon['description'] ?? ''),
            'url' => $url,
            'datePublished' => ! empty($sermon['sermon_date']) ? date('c', strtotime((string) $sermon['sermon_date'])) : null,
            'author' => [
                '@type' => 'Person',
                'name' => (string) ($sermon['minister_name'] ?? 'Minister'),
            ],
        ];

        if ($imageUrl !== '') {
            $graph['image'] = $imageUrl;
        }

        if (! empty($sermon['scripture_refs'])) {
            $graph['about'] = (string) $sermon['scripture_refs'];
        }

        if (! empty($sermon['youtube_url']) || ! empty($sermon['video_embed_code'])) {
            $graph['@type'] = 'VideoObject';
            $graph['contentUrl'] = (string) ($sermon['youtube_url'] ?: $sermon['video_file_path'] ?? '');
            $graph['embedUrl'] = (string) ($sermon['youtube_url'] ?? '');
        } elseif (! empty($sermon['audio_file_path']) || ! empty($sermon['audio_stream_url'])) {
            $graph['@type'] = 'AudioObject';
            $graph['contentUrl'] = (string) ($sermon['audio_stream_url'] ?: $sermon['audio_file_path'] ?? '');
        }

        return $graph;
    }

    /**
     * @param  list<array{question: string, answer: string}>  $faqs
     * @return array<string, mixed>|null
     */
    public function faqPage(array $faqs): ?array
    {
        if ($faqs === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(static fn (array $faq): array => [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ],
            ], $faqs),
        ];
    }
}
