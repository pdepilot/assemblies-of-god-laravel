@php
    $isEdit = ! empty($post);
    $post = is_array($post ?? null) ? $post : [];
    $oldTags = old('tags');
    if ($oldTags === null) {
        $oldTags = is_array($post['tags'] ?? null) ? implode(', ', $post['tags']) : '';
    }
    $published = (string) old('is_published', ! empty($post['is_published']) ? '1' : '0');
@endphp

<div class="field">
    <label for="postTitle">Title</label>
    <input id="postTitle" name="title" value="{{ old('title', $post['title'] ?? '') }}" required placeholder="Sunday reflection: walking in faith">
</div>

@if (! $isEdit)
    <div class="field">
        <label for="postSlug">Slug (optional)</label>
        <input id="postSlug" name="slug" value="{{ old('slug') }}" placeholder="auto-from-title">
    </div>
@endif

<div class="field">
    <label for="postExcerpt">Excerpt</label>
    <input id="postExcerpt" name="excerpt" maxlength="500" value="{{ old('excerpt', $post['excerpt'] ?? '') }}" placeholder="Short teaser for the blog grid">
</div>

<div class="field">
    <label for="postSeoTitle">SEO title</label>
    <input id="postSeoTitle" name="seo_title" maxlength="200" value="{{ old('seo_title', $post['seo_title'] ?? '') }}" placeholder="Google / browser title (defaults to post title)">
</div>

<div class="field">
    <label for="postMetaDescription">SEO description</label>
    <input id="postMetaDescription" name="meta_description" maxlength="255" value="{{ old('meta_description', $post['meta_description'] ?? '') }}" placeholder="About 150–160 characters for Google snippets">
</div>

<div class="field">
    <label for="postTags">Tags (comma separated)</label>
    <input id="postTags" name="tags" value="{{ $oldTags }}" placeholder="prayer, faith, family">
</div>

<div class="field">
    <label for="postAuthor">Author</label>
    <input id="postAuthor" name="author" value="{{ old('author', $post['author'] ?? 'AGC Ikenegbu') }}" placeholder="AGC Ikenegbu">
</div>

<div class="field">
    <label for="postCategory">Category</label>
    <select id="postCategory" name="category">
        @foreach ($categories as $slug => $label)
            <option value="{{ $slug }}" @selected(old('category', $post['category'] ?? 'church-news') === $slug)>{{ $label }}</option>
        @endforeach
    </select>
</div>

<div class="field">
    <label for="postStatus">Workflow status</label>
    <select id="postStatus" name="is_published">
        <option value="0" @selected($published === '0')>Draft</option>
        <option value="1" @selected($published === '1')>Published</option>
    </select>
</div>

<div class="field">
    <label for="coverAlt">Cover ALT text</label>
    <input id="coverAlt" name="featured_image_alt" value="{{ old('featured_image_alt', $post['featured_image_alt'] ?? '') }}" placeholder="Describe the cover image">
</div>

<div class="field">
    <label for="postBody">Body (HTML allowed)</label>
    <textarea class="cms-area" id="postBody" name="body_html" required placeholder="<p>Your church story…</p>">{{ old('body_html', $post['body_html'] ?? '') }}</textarea>
</div>

<label class="dropzone" id="blogCoverDropzone">
    <strong>Drop cover image</strong>
    <span>or click to choose · JPG/PNG/WebP · max 5MB</span>
    <input type="file" id="coverFile" name="featured_image" accept="image/jpeg,image/png,image/webp,image/jpg">
    <img
        class="preview-shot{{ ! empty($featuredImageUrl) ? ' show' : '' }}"
        id="coverPreview"
        src="{{ $featuredImageUrl ?? '' }}"
        alt=""
    >
</label>

@if ($isEdit && ! empty($featuredImageUrl))
    <label class="remove-cover-row">
        <input type="checkbox" name="remove_featured_image" value="1" @checked(old('remove_featured_image'))>
        Remove current cover image
    </label>
@endif

<button type="button" class="mini-btn" id="coverClear">Remove cover selection</button>
