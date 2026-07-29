<?php

namespace App\Http\Controllers\Website;

use App\Http\Requests\Website\SaveBlogPostRequest;
use App\Models\Admin;
use App\Models\AgBlogPost;
use App\Policies\WebsitePolicy;
use App\Services\Website\BlogReadService;
use App\Services\Website\BlogWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class BlogController
{
    public function __construct(
        private readonly BlogReadService $read,
        private readonly BlogWriteService $write,
        private readonly WebsitePolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewWebsite($admin);

        return view('website.blog.index', [
            'result' => $this->read->listPosts(
                (string) $request->query('q', ''),
                (string) $request->query('category', ''),
                (string) $request->query('status', ''),
                max(1, (int) $request->query('page', 1)),
            ),
            'categories' => BlogReadService::categoryLabels(),
            'stats' => $this->read->getStats(),
            'canManage' => $this->policy->manageWebsite($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageWebsite($this->admin());

        return view('website.blog.create', [
            'categories' => BlogReadService::categoryLabels(),
        ]);
    }

    public function store(SaveBlogPostRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);

        try {
            $this->write->save(
                $request->safe()->except(['featured_image', 'remove_featured_image']),
                (int) $admin->id,
                $request->file('featured_image'),
                $request->boolean('remove_featured_image'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['featured_image' => $e->getMessage()]);
        }

        return redirect()->route('website.blog.index')->with('status', 'Blog post saved.');
    }

    public function show(AgBlogPost $post): View
    {
        $admin = $this->admin();
        $this->policy->requireViewWebsite($admin);
        $row = $this->read->getPost((int) $post->id);
        abort_if($row === null, 404);

        return view('website.blog.show', [
            'post' => $row,
            'canManage' => $this->policy->manageWebsite($admin),
            'featuredImageUrl' => $this->featuredImageUrl($row),
        ]);
    }

    public function edit(AgBlogPost $post): View
    {
        $this->policy->requireManageWebsite($this->admin());
        $row = $this->read->getPost((int) $post->id);
        abort_if($row === null, 404);

        return view('website.blog.edit', [
            'post' => $row,
            'categories' => BlogReadService::categoryLabels(),
            'featuredImageUrl' => $this->featuredImageUrl($row),
        ]);
    }

    public function update(SaveBlogPostRequest $request, AgBlogPost $post): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);

        $data = $request->safe()->except(['featured_image', 'remove_featured_image']);
        $data['id'] = (int) $post->id;

        try {
            $this->write->save(
                $data,
                (int) $admin->id,
                $request->file('featured_image'),
                $request->boolean('remove_featured_image'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['featured_image' => $e->getMessage()]);
        }

        return redirect()->route('website.blog.show', $post)->with('status', 'Blog post updated.');
    }

    public function publish(AgBlogPost $post): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);
        $this->write->setPublished((int) $post->id, true, (int) $admin->id);

        return back()->with('status', 'Post published.');
    }

    /** @param array<string, mixed> $post */
    private function featuredImageUrl(array $post): ?string
    {
        $path = trim((string) ($post['featured_image'] ?? ''));
        if ($path === '') {
            return null;
        }

        return app(\App\Services\PublicSite\PublicAssetResolver::class)->url($path);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
