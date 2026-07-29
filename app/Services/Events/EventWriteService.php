<?php

namespace App\Services\Events;

use App\Models\ChurchEvent;
use App\Services\Security\SecurityAuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class EventWriteService
{
    private const MAX_IMAGE_BYTES = 5242880;

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly EventReadService $read,
        private readonly SecurityAuditService $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data, ?UploadedFile $image, int $adminId): array
    {
        $validated = $this->validate($data);
        $uploadedPath = $this->storeImage($image);
        if ($uploadedPath !== null) {
            $validated['image_path'] = $uploadedPath;
        }

        $event = ChurchEvent::query()->create([
            ...$validated,
            'event_code' => $this->generateCode(),
            'created_by' => $adminId,
        ]);

        $this->audit->log(
            'event_created',
            'Event "'.($validated['title'] ?? 'Untitled').'" was scheduled.',
            $adminId,
            'info',
            ['event_id' => (int) $event->id],
        );

        return $this->read->getEvent((int) $event->id) ?? $event->toArray();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data, ?UploadedFile $image, bool $removeImage, int $adminId): array
    {
        $event = ChurchEvent::query()->findOrFail($id);
        $validated = $this->validate($data, (string) ($event->image_path ?? EventReadService::IMAGES[0]));
        $imagePath = (string) $validated['image_path'];

        $uploadedPath = $this->storeImage($image);
        if ($uploadedPath !== null) {
            $this->deleteUploadedImage((string) $event->image_path);
            $imagePath = $uploadedPath;
        } elseif ($removeImage) {
            $this->deleteUploadedImage((string) $event->image_path);
            $imagePath = EventReadService::IMAGES[0];
        }

        $event->update([
            ...$validated,
            'image_path' => $imagePath,
            'updated_by' => $adminId,
        ]);

        return $this->read->getEvent($id) ?? $event->fresh()->toArray();
    }

    /** @return array<string, mixed> */
    public function setPublished(int $id, bool $published, int $adminId): array
    {
        $event = ChurchEvent::query()->find($id);
        if (! $event) {
            throw new InvalidArgumentException('Event not found.');
        }

        $event->update([
            'is_published' => $published,
            'updated_by' => $adminId,
        ]);

        return $this->read->getEvent($id) ?? $event->fresh()->toArray();
    }

    public function delete(int $id): void
    {
        $event = ChurchEvent::query()->find($id);
        if (! $event) {
            throw new InvalidArgumentException('Event not found.');
        }

        $this->deleteUploadedImage((string) $event->image_path);
        $event->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function validate(array $data, ?string $fallbackImage = null): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '' || mb_strlen($title) < 2) {
            throw new InvalidArgumentException('Event title is required.');
        }

        $eventDate = trim((string) ($data['event_date'] ?? ''));
        if ($eventDate === '') {
            throw new InvalidArgumentException('Event date is required.');
        }

        $category = (string) ($data['category'] ?? 'other');
        if (! in_array($category, EventReadService::CATEGORIES, true)) {
            $category = 'other';
        }

        $status = (string) ($data['status'] ?? 'upcoming');
        if (! in_array($status, EventReadService::STATUSES, true)) {
            $status = 'upcoming';
        }

        $icon = trim((string) ($data['icon_class'] ?? 'fa-church'));
        $icon = preg_replace('/\s+/', ' ', $icon) ?? $icon;
        if (str_starts_with($icon, 'fa ')) {
            $icon = trim(substr($icon, 3));
        }
        if ($icon !== '' && ! str_starts_with($icon, 'fa-')) {
            $icon = 'fa-'.$icon;
        }
        if ($icon === '' || ! in_array($icon, EventReadService::ICONS, true)) {
            $icon = 'fa-church';
        }

        $image = trim((string) ($fallbackImage ?? EventReadService::IMAGES[0]));
        $image = ltrim(str_replace('\\', '/', $image), '/');
        if ($image === '' || ! $this->isAllowedImagePath($image)) {
            $image = EventReadService::IMAGES[0];
        }

        $ctaUrl = trim((string) ($data['cta_url'] ?? 'contact'));
        if ($ctaUrl === '') {
            $ctaUrl = 'contact';
        }
        if (! $this->isAllowedCtaUrl($ctaUrl)) {
            throw new InvalidArgumentException('Button link must be a valid URL or site path (e.g. contact, donate, /about).');
        }

        $publicCat = trim((string) ($data['public_category_label'] ?? ''));
        if ($publicCat === '') {
            $publicCat = EventReadService::publicCategoryLabels()[$category] ?? 'Event';
        }

        $time = trim((string) ($data['event_time'] ?? ''));
        $recurrenceLabel = trim((string) ($data['recurrence_label'] ?? ''));

        return [
            'title' => $title,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'event_date' => $eventDate,
            'event_time' => $time !== '' ? $time : null,
            'location' => trim((string) ($data['location'] ?? '')) ?: '',
            'category' => $category,
            'image_path' => $image,
            'recurrence_label' => $recurrenceLabel !== '' ? $recurrenceLabel : null,
            'schedule_display' => trim((string) ($data['schedule_display'] ?? '')) ?: null,
            'public_category_label' => $publicCat,
            'icon_class' => $icon,
            'cta_text' => trim((string) ($data['cta_text'] ?? 'Learn more')) ?: 'Learn more',
            'cta_url' => $ctaUrl,
            'is_recurring' => (! empty($data['is_recurring']) && (string) $data['is_recurring'] !== '0') || $recurrenceLabel !== '',
            'is_published' => ! empty($data['is_published']) && (string) $data['is_published'] !== '0',
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'expected_attendance' => max(0, (int) ($data['expected_attendance'] ?? 0)),
            'status' => $status,
        ];
    }

    private function isAllowedImagePath(string $path): bool
    {
        if (in_array($path, EventReadService::IMAGES, true)) {
            return true;
        }

        return (bool) preg_match('~^uploads/events/[a-zA-Z0-9._-]+\.(jpg|jpeg|png|webp)$~i', $path)
            || (bool) preg_match('~^(img|images)/[a-zA-Z0-9._/-]+\.(jpg|jpeg|png|webp)$~i', $path);
    }

    private function isAllowedCtaUrl(string $url): bool
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL)) {
            return true;
        }
        if (str_starts_with($url, '/')) {
            return true;
        }

        return (bool) preg_match('~^[A-Za-z0-9][A-Za-z0-9_./?#&=%-]*$~', $url);
    }

    private function storeImage(?UploadedFile $image): ?string
    {
        if (! $image) {
            return null;
        }

        if (! isset(self::ALLOWED_IMAGE_TYPES[$image->getMimeType() ?? ''])) {
            throw new InvalidArgumentException('Event image must be JPG, PNG, or WebP.');
        }
        if ($image->getSize() > self::MAX_IMAGE_BYTES) {
            throw new InvalidArgumentException('Event image must be 5 MB or smaller.');
        }

        $ext = self::ALLOWED_IMAGE_TYPES[$image->getMimeType()];
        $filename = Str::random(32).'.'.$ext;
        $relativePath = 'uploads/events/'.$filename;

        $directory = public_path('site/uploads/events');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create event upload directory.');
        }

        $destination = $directory.DIRECTORY_SEPARATOR.$filename;
        if (! $image->move($directory, $filename)) {
            throw new RuntimeException('Unable to save uploaded image.');
        }

        return $relativePath;
    }

    private function deleteUploadedImage(string $path): void
    {
        if (! str_starts_with($path, 'uploads/events/')) {
            return;
        }

        $sitePath = public_path('site/'.ltrim(str_replace('\\', '/', $path), '/'));
        if (is_file($sitePath)) {
            @unlink($sitePath);

            return;
        }

        $storagePath = storage_path('app/public/'.ltrim(str_replace('\\', '/', $path), '/'));
        if (is_file($storagePath)) {
            @unlink($storagePath);
        }
    }

    private function generateCode(): string
    {
        for ($i = 0; $i < 5000; $i++) {
            $code = 'EVT'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            if (! DB::table('church_events')->where('event_code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Unable to allocate a unique event code.');
    }
}
