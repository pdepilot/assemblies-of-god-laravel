<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Website\BlogReadService;
use App\Services\Website\SchemaBuilder;
use App\Services\Website\SeoReadService;
use App\Services\Website\WebsitePagesReadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class BlogController extends Controller
{
    public function __construct(
        private readonly BlogReadService $blog,
        private readonly PublicHomepageReadService $homepage,
        private readonly WebsitePagesReadService $pages,
        private readonly PublicAssetResolver $assets,
        private readonly SeoReadService $seo,
        private readonly SchemaBuilder $schema,
    ) {}

    public function index(Request $request): View
    {
        return $this->listing($request, '', '', 'Blog');
    }

    public function category(Request $request, string $category): View
    {
        if (! isset(BlogReadService::categoryLabels()[$category])) {
            throw new NotFoundHttpException('Category not found.');
        }

        return $this->listing($request, $category, '', BlogReadService::categoryLabels()[$category]);
    }

    public function tag(Request $request, string $tag): View
    {
        return $this->listing($request, '', $tag, 'Tag: '.$tag);
    }

    public function suggest(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        return response()->json([
            'suggestions' => $this->blog->suggest($query),
        ]);
    }

    public function show(string $slug): View
    {
        $brandShortName = (string) config('identity.public.short_name', 'AGC Ikenegbu');
        $post = $this->blog->getPublishedBySlug($slug);
        if ($post === null) {
            throw new NotFoundHttpException('Blog post not found.');
        }

        $this->blog->incrementViews((int) $post['id']);
        $post = $this->hydratePost($post);
        $toc = $this->blog->tableOfContents((string) ($post['body_html'] ?? ''));
        $post['body_html'] = $this->blog->injectHeadingIds((string) ($post['body_html'] ?? ''), $toc);
        $related = array_map(fn (array $p) => $this->hydratePost($p), $this->blog->relatedPosts($post));
        $adjacent = $this->blog->adjacentPosts($post);
        if ($adjacent['previous']) {
            $adjacent['previous'] = $this->hydratePost($adjacent['previous']);
        }
        if ($adjacent['next']) {
            $adjacent['next'] = $this->hydratePost($adjacent['next']);
        }

        $payload = $this->homepage->chrome();
        $canonical = url('/blog/'.$slug);
        $seo = $this->seo->forKey('blog', $canonical);
        $seoTitle = trim((string) ($post['seo_title'] ?? ''));
        $seo['title'] = ($seoTitle !== '' ? $seoTitle : trim((string) ($post['title'] ?? 'Blog'))).' | '.$brandShortName;
        if (trim((string) ($post['meta_description'] ?? '')) !== '') {
            $seo['meta_description'] = (string) $post['meta_description'];
        } elseif (trim((string) ($post['excerpt'] ?? '')) !== '') {
            $seo['meta_description'] = (string) $post['excerpt'];
        }
        if (trim((string) ($post['featured_image'] ?? '')) !== '') {
            $seo['og_image'] = (string) $post['featured_image'];
        }

        return view('public.blog.show', [
            'church' => $payload['church'],
            'post' => $post,
            'toc' => $toc,
            'related' => $related,
            'adjacent' => $adjacent,
            'popular' => array_map(fn (array $p) => $this->hydratePost($p), $this->blog->popular(5)),
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
            'schemaGraphs' => [
                $this->schema->article($post, $canonical, (string) ($post['image_url'] ?? '')),
                $this->schema->breadcrumbs([
                    ['name' => 'Home', 'url' => url('/')],
                    ['name' => 'Blog', 'url' => route('public.blog')],
                    ['name' => (string) $post['title'], 'url' => $canonical],
                ]),
            ],
            'bodyClass' => 'has-reading-progress',
            'testimonySourcePage' => 'blog',
        ]);
    }

    private function listing(Request $request, string $category, string $tag, string $headingOverride): View
    {
        $brandShortName = (string) config('identity.public.short_name', 'AGC Ikenegbu');
        $pageNum = max(1, (int) $request->query('page', 1));
        $query = trim((string) $request->query('q', ''));
        $posts = $this->blog->listPublished($query, $category, $pageNum, 9, $tag);
        $payload = $this->homepage->chrome();
        $page = $this->hydratePageChrome($this->pages->getPage('blog'));
        $posts['items'] = array_map(fn (array $post) => $this->hydratePost($post), $posts['items']);

        $seo = $this->seo->forKey('blog', url()->current());
        if ($headingOverride !== 'Blog') {
            $seo['title'] = $headingOverride.' | '.$brandShortName;
        }

        return view('public.blog.index', [
            'church' => $payload['church'],
            'posts' => $posts,
            'page' => $page,
            'listingHeading' => $headingOverride,
            'activeCategory' => $category,
            'activeTag' => $tag,
            'searchQuery' => $query,
            'popular' => array_map(fn (array $p) => $this->hydratePost($p), $this->blog->popular(5)),
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
            'schemaGraphs' => $this->schema->organizationAndChurch(),
            'testimonySourcePage' => 'blog',
        ]);
    }

    /** @param array<string, mixed> $page @return array<string, mixed> */
    private function hydratePageChrome(array $page): array
    {
        $hero = trim((string) ($page['hero_image'] ?? ''));
        $page['hero_image_url'] = $hero !== '' ? $this->assets->url($hero) : null;

        return $page;
    }

    /** @param array<string, mixed> $post @return array<string, mixed> */
    private function hydratePost(array $post): array
    {
        $image = trim((string) ($post['featured_image'] ?? ''));
        $post['image_url'] = $image !== ''
            ? $this->assets->url($image)
            : $this->assets->url('images/main1.jpg');
        $post['category_label'] = BlogReadService::categoryLabels()[(string) ($post['category'] ?? '')] ?? 'Church News';
        $post['published_display'] = ! empty($post['published_at'])
            ? date('M j, Y', strtotime((string) $post['published_at']))
            : '';
        $post['image_alt'] = trim((string) ($post['featured_image_alt'] ?? '')) !== ''
            ? (string) $post['featured_image_alt']
            : (string) ($post['title'] ?? 'Blog image');
        $readingMinutes = max(1, (int) ($post['reading_time_minutes'] ?? 0));
        $post['reading_time_display'] = $readingMinutes.' min read';
        $author = trim((string) ($post['author'] ?? ''));
        $post['author_display'] = $author !== '' ? $author : (string) config('identity.public.short_name', 'AGC Ikenegbu');
        $post['view_count_display'] = max(0, (int) ($post['view_count'] ?? 0));

        return $post;
    }
}
