<?php

namespace App\Services\Newsletter;

use Illuminate\Support\Facades\DB;

final class SubscriberReadService
{
    public const STATUSES = ['active', 'unsubscribed'];

    public function __construct(
        private readonly NewsletterLocationService $locations,
    ) {}

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, pages: int} */
    public function listSubscribers(string $query = '', string $status = '', string $source = '', int $page = 1, int $perPage = 25): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        $builder = DB::table('site_newsletter_subscribers');
        if ($query !== '') {
            $builder->where('email', 'like', '%' . $query . '%');
        }
        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $builder->where('status', $status);
        }
        if ($source !== '') {
            $builder->where('source', $source);
        }

        $total = (clone $builder)->count();
        $items = $builder->orderByDesc('subscribed_at')
            ->orderByDesc('id')
            ->offset($offset)
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => $this->presentSubscriber((array) $row))
            ->all();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pages' => (int) max(1, ceil($total / $perPage)),
        ];
    }

    /** @return array<string, mixed>|null */
    public function getSubscriber(int $id): ?array
    {
        $row = DB::table('site_newsletter_subscribers')->where('id', $id)->first();

        return $row ? $this->presentSubscriber((array) $row) : null;
    }

    /** @param  array<string, mixed>  $row @return array<string, mixed> */
    private function presentSubscriber(array $row): array
    {
        $location = $this->locations->presentation($row);
        $row['location_display'] = $location['location'];
        $row['location_source_label'] = $location['source_label'];
        $row['location_accuracy_label'] = $location['accuracy_label'];

        return $row;
    }

    /** @return array<string, int> */
    public function getStats(): array
    {
        return [
            'total' => (int) DB::table('site_newsletter_subscribers')->count(),
            'active' => (int) DB::table('site_newsletter_subscribers')->where('status', 'active')->count(),
            'unsubscribed' => (int) DB::table('site_newsletter_subscribers')->where('status', 'unsubscribed')->count(),
        ];
    }
}
