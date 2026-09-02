<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Blog Studio</h2></x-slot>

    @push('head')
        <link href="{{ asset('portal/css/blog-studio.css') }}?v={{ @filemtime(public_path('portal/css/blog-studio.css')) }}" rel="stylesheet">
    @endpush

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6 blog-studio">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @include('website._nav', ['canManage' => $canManage])

            <header class="studio-head">
                <p class="studio-eyebrow">Website · Church journal</p>
                <h1 class="studio-title">Blog Studio</h1>
                <p class="studio-copy">
                    Write and publish devotionals and church news that appear on the public <strong>/blog</strong> page.
                    Drafts stay admin-only until you publish.
                </p>
            </header>

            <div class="metric-rail">
                <article class="metric">
                    <span class="label">Total posts</span>
                    <span class="value">{{ (int) ($stats['total'] ?? 0) }}</span>
                    <span class="delta">All statuses</span>
                </article>
                <article class="metric">
                    <span class="label">Published</span>
                    <span class="value">{{ (int) ($stats['published'] ?? 0) }}</span>
                    <span class="delta">On /blog</span>
                </article>
                <article class="metric">
                    <span class="label">Drafts</span>
                    <span class="value">{{ (int) ($stats['drafts'] ?? 0) }}</span>
                    <span class="delta">Admin only</span>
                </article>
                <article class="metric">
                    <span class="label">Preview</span>
                    <span class="value" style="font-size:1rem;">Open</span>
                    <span class="delta"><a href="{{ route('public.blog') }}" target="_blank" rel="noopener" class="text-indigo-600">View blog</a></span>
                </article>
            </div>

            <div class="content-split">
                <div class="surface" data-stamp="Compose">
                    <div class="panel-title">
                        <div>
                            <h2>New church post</h2>
                            <p>Title, cover, body HTML</p>
                        </div>
                    </div>

                    @if ($canManage)
                        @if ($errors->any())
                            <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 mb-4">
                                <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('website.blog.store') }}" enctype="multipart/form-data" id="blogForm">
                            @csrf
                            @include('website.blog._form', [
                                'categories' => $categories,
                                'post' => null,
                                'featuredImageUrl' => null,
                            ])
                            <div class="form-actions">
                                <button class="solid-btn" type="submit">Save post</button>
                                <a class="ghost-btn" href="{{ route('website.blog.create') }}">Open full editor</a>
                            </div>
                        </form>
                    @else
                        <p class="text-sm text-gray-500">You can view blog posts, but only content managers can compose new entries.</p>
                    @endif
                </div>

                <div class="surface" data-stamp="Posts">
                    <div class="panel-title">
                        <div>
                            <h2>Post deck</h2>
                            <p>Edit or review journal entries</p>
                        </div>
                    </div>

                    <div class="media-grid">
                        @forelse ($result['items'] as $item)
                            <article class="media-card">
                                <div class="thumb">
                                    @if (! empty($item['featured_image_url']))
                                        <img src="{{ $item['featured_image_url'] }}" alt="{{ $item['featured_image_alt'] ?? $item['title'] }}">
                                    @endif
                                </div>
                                <div class="body">
                                    <span class="status-chip {{ ! empty($item['is_published']) ? 'published' : 'draft' }}">
                                        {{ ! empty($item['is_published']) ? 'Published' : 'Draft' }}
                                    </span>
                                    <h3>{{ $item['title'] }}</h3>
                                    <p>{{ \Illuminate\Support\Str::limit((string) ($item['excerpt'] ?? ''), 90) }}</p>
                                    <div class="row-actions">
                                        <a href="{{ route('website.blog.show', $item['id']) }}">View</a>
                                        @if ($canManage)
                                            <a href="{{ route('website.blog.edit', $item['id']) }}">Edit</a>
                                        @endif
                                        @if (! empty($item['is_published']) && ! empty($item['slug']))
                                            <a href="{{ route('public.blog.show', $item['slug']) }}" target="_blank" rel="noopener">Public</a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <p class="text-sm text-gray-500">No blog posts found.</p>
                        @endforelse
                    </div>

                    @if (($result['pages'] ?? 1) > 1)
                        <div class="blog-admin-pager">
                            @if (($result['page'] ?? 1) > 1)
                                <a class="ghost-btn" href="{{ request()->fullUrlWithQuery(['page' => $result['page'] - 1]) }}">Previous</a>
                            @else
                                <span class="ghost-btn" style="opacity:.45;pointer-events:none;">Previous</span>
                            @endif
                            <span class="blog-admin-pager-meta">
                                Page {{ $result['page'] }} of {{ $result['pages'] }}
                            </span>
                            @if (($result['page'] ?? 1) < ($result['pages'] ?? 1))
                                <a class="ghost-btn" href="{{ request()->fullUrlWithQuery(['page' => $result['page'] + 1]) }}">Next</a>
                            @else
                                <span class="ghost-btn" style="opacity:.45;pointer-events:none;">Next</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('portal/js/blog-studio.js') }}?v={{ @filemtime(public_path('portal/js/blog-studio.js')) }}" defer></script>
    @endpush
</x-app-layout>
