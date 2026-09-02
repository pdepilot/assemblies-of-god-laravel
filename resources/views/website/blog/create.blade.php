<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">New Blog Post</h2></x-slot>

    @push('head')
        <link href="{{ asset('portal/css/blog-studio.css') }}?v={{ @filemtime(public_path('portal/css/blog-studio.css')) }}" rel="stylesheet">
    @endpush

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 blog-studio">
            @if ($errors->any())
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800 mb-4">
                    <ul class="list-disc pl-4">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="surface">
                <div class="panel-title">
                    <div>
                        <h2>New church post</h2>
                        <p>Title, cover, body HTML</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('website.blog.store') }}" enctype="multipart/form-data" id="blogForm">
                    @csrf
                    @include('website.blog._form', [
                        'categories' => $categories,
                        'post' => null,
                        'featuredImageUrl' => null,
                    ])
                    <div class="form-actions">
                        <button class="solid-btn" type="submit">Save post</button>
                        <a class="ghost-btn" href="{{ route('website.blog.index') }}">Back to studio</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="{{ asset('portal/js/blog-studio.js') }}?v={{ @filemtime(public_path('portal/js/blog-studio.js')) }}" defer></script>
    @endpush
</x-app-layout>
