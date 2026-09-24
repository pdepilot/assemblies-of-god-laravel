@php
    $isEdit = isset($sermon) && is_array($sermon);
    $row = $isEdit ? $sermon : [];
    $resolver = app(\App\Services\PublicSite\PublicAssetResolver::class);
    $coverPath = (string) ($row['featured_image'] ?? '');
    $videoPath = (string) ($row['video_file_path'] ?? '');
    $coverUrl = $coverPath !== '' ? $resolver->url($coverPath) : '';
    $videoUrl = $videoPath !== '' ? $resolver->url($videoPath) : '';
    $field = static function (string $name, mixed $fallback = '') use ($row): string {
        return (string) old($name, $row[$name] ?? $fallback);
    };
@endphp

@if ($errors->any())
    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4 text-sm text-red-800 dark:text-red-200">{{ $errors->first() }}</div>
@endif

<div class="sermon-studio">
    <div class="sermon-studio__media">
        <div class="sermon-media-card">
            <div class="sermon-media-card__icon" aria-hidden="true"><i class="fas fa-image"></i></div>
            <h3 class="sermon-media-card__title">Cover image</h3>
            <p class="sermon-media-card__hint">Poster shown in the sermon library. JPG, PNG, or WebP up to 2 MB.</p>
            <div class="sermon-media-card__preview" id="sermonImagePreview">
                @if ($coverUrl)
                    <img src="{{ $coverUrl }}" alt="Current cover">
                @else
                    <span>No image yet</span>
                @endif
            </div>
            @if ($isEdit && $coverPath !== '')
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 mt-2">
                    <input type="checkbox" name="remove_featured_image" value="1" class="rounded border-gray-300"> Remove current image
                </label>
            @endif
            <input id="featured_image" name="featured_image" type="file" accept="image/jpeg,image/png,image/webp" class="mt-3 block w-full text-sm">
            <x-input-error :messages="$errors->get('featured_image')" class="mt-2" />
        </div>

        <div class="sermon-media-card">
            <div class="sermon-media-card__icon" aria-hidden="true"><i class="fas fa-video"></i></div>
            <h3 class="sermon-media-card__title">Sermon video</h3>
            <p class="sermon-media-card__hint">Upload MP4, WebM, or MOV. Maximum size <strong>15 MB</strong>. Optional YouTube link below if you prefer not to upload.</p>
            <div class="sermon-media-card__preview sermon-media-card__preview--video" id="sermonVideoPreview">
                @if ($videoUrl)
                    <video src="{{ $videoUrl }}" controls preload="metadata"></video>
                @else
                    <span>No video yet</span>
                @endif
            </div>
            @if ($isEdit && $videoPath !== '')
                <label class="inline-flex items-center gap-2 text-sm text-gray-600 dark:text-gray-300 mt-2">
                    <input type="checkbox" name="remove_video" value="1" class="rounded border-gray-300"> Remove current video
                </label>
            @endif
            <input id="video_file" name="video_file" type="file" accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov,.m4v" class="mt-3 block w-full text-sm">
            <p class="text-xs text-gray-500 mt-1" id="sermonVideoStatus" role="status"></p>
            <x-input-error :messages="$errors->get('video_file')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="sm:col-span-2">
            <x-input-label for="title" value="Title" :required="true" />
            <x-text-input id="title" name="title" class="block mt-1 w-full" required :value="$field('title')" />
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="sermon_date" value="Sermon date" :required="true" />
            <x-text-input id="sermon_date" name="sermon_date" type="date" class="block mt-1 w-full" required :value="$field('sermon_date', date('Y-m-d'))" />
        </div>
        <div>
            <x-input-label for="minister_name" value="Minister" />
            <x-text-input id="minister_name" name="minister_name" class="block mt-1 w-full" :value="$field('minister_name')" />
        </div>
        <div>
            <x-input-label for="sermon_type" value="Type" />
            <select id="sermon_type" name="sermon_type" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected($field('sermon_type', $isEdit ? '' : 'video') === $type)>{{ ucfirst($type) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($field('status', 'draft') === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        @if (! empty($categories))
            <div>
                <x-input-label for="category_id" value="Category" />
                <select id="category_id" name="category_id" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">None</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category['id'] }}" @selected((string) $field('category_id') === (string) $category['id'])>{{ $category['name'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        @if (! empty($series))
            <div>
                <x-input-label for="series_id" value="Series" />
                <select id="series_id" name="series_id" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">
                    <option value="">None</option>
                    @foreach ($series as $item)
                        <option value="{{ $item['id'] }}" @selected((string) $field('series_id') === (string) $item['id'])>{{ $item['title'] }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="sm:col-span-2">
            <x-input-label for="scripture_refs" value="Scripture" />
            <x-text-input id="scripture_refs" name="scripture_refs" class="block mt-1 w-full" :value="$field('scripture_refs')" placeholder="John 3:16" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="description" value="Short description" />
            <textarea id="description" name="description" rows="3" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ $field('description') }}</textarea>
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="youtube_url" value="YouTube URL" />
            <x-text-input id="youtube_url" name="youtube_url" class="block mt-1 w-full" :value="$field('youtube_url')" placeholder="https://www.youtube.com/watch?v=…" />
            <p class="text-xs text-gray-500 mt-1">Use this if the message is already on YouTube instead of (or as well as) an uploaded file.</p>
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="seo_title" value="SEO title" />
            <x-text-input id="seo_title" name="seo_title" class="block mt-1 w-full" maxlength="255" :value="$field('seo_title')" />
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="seo_description" value="SEO description" />
            <textarea id="seo_description" name="seo_description" rows="2" maxlength="500" class="block mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900">{{ $field('seo_description') }}</textarea>
        </div>
        <div class="sm:col-span-2">
            <x-input-label for="tags" value="Tags (comma-separated)" />
            @php
                $tagValue = old('tags');
                if ($tagValue === null && $isEdit) {
                    $rawTags = $row['tags'] ?? [];
                    if (is_string($rawTags)) {
                        $decoded = json_decode($rawTags, true);
                        $rawTags = is_array($decoded) ? $decoded : [];
                    }
                    $tagValue = is_array($rawTags) ? implode(', ', $rawTags) : '';
                }
            @endphp
            <x-text-input id="tags" name="tags" class="block mt-1 w-full" :value="$tagValue ?? ''" placeholder="faith, prayer, holy-spirit" />
        </div>
    </div>
</div>

<script>
(function () {
    var maxVideo = 15 * 1024 * 1024;
    var imageInput = document.getElementById('featured_image');
    var videoInput = document.getElementById('video_file');
    var imagePreview = document.getElementById('sermonImagePreview');
    var videoPreview = document.getElementById('sermonVideoPreview');
    var status = document.getElementById('sermonVideoStatus');
    var typeSelect = document.getElementById('sermon_type');

    function setPreview(box, html) {
        if (box) box.innerHTML = html;
    }

    if (imageInput) {
        imageInput.addEventListener('change', function () {
            var file = imageInput.files && imageInput.files[0];
            if (!file) return;
            var url = URL.createObjectURL(file);
            setPreview(imagePreview, '<img src="' + url + '" alt="Selected cover">');
        });
    }

    if (videoInput) {
        videoInput.addEventListener('change', function () {
            var file = videoInput.files && videoInput.files[0];
            if (!file) return;
            if (file.size > maxVideo) {
                videoInput.value = '';
                if (status) status.textContent = 'Video must be 15 MB or smaller.';
                return;
            }
            if (status) status.textContent = file.name + ' · ' + Math.round(file.size / (1024 * 1024) * 10) / 10 + ' MB';
            var url = URL.createObjectURL(file);
            setPreview(videoPreview, '<video src="' + url + '" controls preload="metadata"></video>');
            if (typeSelect && typeSelect.value === 'audio') typeSelect.value = 'video';
        });
    }
})();
</script>
