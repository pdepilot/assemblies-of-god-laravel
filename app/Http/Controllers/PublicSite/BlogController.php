<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\PublicSite\PublicHomepageReadService;
use App\Services\Website\BlogReadService;
use App\Services\Website\SeoReadService;
use App\Services\Website\WebsitePagesReadService;
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
    ) {}

    public function index(Request $request): View
    {
        $pageNum = max(1, (int) $request->query('page', 1));
        $posts = $this->blog->listPublished('', '', $pageNum, 9);
        $payload = $this->homepage->payload();
        $page = $this->hydratePageChrome($this->pages->getPage('blog'));

        $posts['items'] = array_map(fn (array $post) => $this->hydratePost($post), $posts['items']);

        $seo = $this->seo->forKey('blog', url('/blog'));
        $defaultHeading = (string) ($this->pages->defaultPageContent('blog')['heading'] ?? 'Blog');
        $pageHeading = trim((string) ($page['heading'] ?? ''));
        if ($pageHeading !== '' && strcasecmp($pageHeading, $defaultHeading) !== 0) {
            $seo['title'] = $pageHeading.' | AG Ikenebgu';
        }
        if (trim((string) ($page['intro'] ?? '')) !== '') {
            $seo['meta_description'] = \Illuminate\Support\Str::limit(strip_tags((string) $page['intro']), 160, '');
        }
        if (trim((string) ($page['hero_image'] ?? '')) !== '') {
            $seo['og_image'] = (string) $page['hero_image'];
        }

        return view('public.blog.index', [
            'church' => $payload['church'],
            'posts' => $posts,
            'page' => $page,
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
            'testimonySourcePage' => 'blog',
        ]);
    }

    public function show(string $slug): View
    {
        $post = $this->blog->getPublishedBySlug($slug);
        if ($post === null) {
            throw new NotFoundHttpException('Blog post not found.');
        }

        $post = $this->hydratePost($post);
        $payload = $this->homepage->payload();
        $seo = $this->seo->forKey('blog', url('/blog/'.$slug));
        $seo['title'] = trim((string) ($post['title'] ?? 'Blog')).' | AG Ikenebgu';
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
            'legacy_api_base' => $payload['legacy_api_base'],
            'traffic_beacon_url' => $payload['traffic_beacon_url'],
            'seo' => $seo,
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

        return $post;
    }
}
