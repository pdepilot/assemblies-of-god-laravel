<?php

namespace App\Jobs;

use App\Models\SiteNewsletterSubscriber;
use App\Services\Newsletter\NewsletterLocationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Schema;

final class BackfillNewsletterSubscriberIpLocationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public readonly int $subscriberId,
    ) {}

    public function handle(NewsletterLocationService $locations): void
    {
        if (! Schema::hasColumn('site_newsletter_subscribers', 'location_source')) {
            return;
        }

        $subscriber = SiteNewsletterSubscriber::query()->find($this->subscriberId);
        if ($subscriber === null) {
            return;
        }

        if (trim((string) ($subscriber->country ?? '')) !== '' || trim((string) ($subscriber->region ?? '')) !== '') {
            return;
        }

        if ((string) ($subscriber->location_source ?? '') === NewsletterLocationService::SOURCE_USER) {
            return;
        }

        $ip = trim((string) ($subscriber->ip_address ?? ''));
        if ($ip === '') {
            return;
        }

        $resolved = $locations->resolveFromRequest([], $ip);
        if ($resolved === [] || ! $locations->shouldReplace((string) ($subscriber->location_source ?? ''), (string) ($resolved['location_source'] ?? ''))) {
            return;
        }

        $subscriber->fill($resolved)->save();
    }
}
