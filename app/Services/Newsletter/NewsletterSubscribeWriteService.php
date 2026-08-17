<?php

namespace App\Services\Newsletter;

use App\Jobs\BackfillNewsletterSubscriberIpLocationJob;
use App\Models\SiteNewsletterSubscriber;
use App\Services\Security\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class NewsletterSubscribeWriteService
{
    public function __construct(
        private readonly NewsletterLocationService $locations,
        private readonly SecurityAuditService $audit,
    ) {}

    /**
     * @return array{success: bool, message: string, created: bool, reactivated: bool}
     */
    public function subscribe(Request $request): array
    {
        if (trim((string) $request->input('website')) !== '') {
            return [
                'success' => true,
                'message' => 'Thank you for subscribing.',
                'created' => false,
                'reactivated' => false,
            ];
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        $email = strtolower(trim($validated['email']));
        $source = trim((string) ($validated['source'] ?? 'footer')) ?: 'footer';

        $existing = SiteNewsletterSubscriber::query()->where('email', $email)->first();
        if ($existing && ($existing->status ?? '') === 'active') {
            return [
                'success' => true,
                'message' => 'You are already subscribed. Thank you!',
                'created' => false,
                'reactivated' => false,
            ];
        }

        $hasConfirm = Schema::hasColumn('site_newsletter_subscribers', 'confirm_token');
        $activateNow = ! $hasConfirm
            || app()->environment(['local', 'testing'])
            || ! config('mail.default')
            || in_array((string) config('mail.default'), ['log', 'array'], true);

        $payload = [
            'email' => $email,
            'source' => $source,
            'status' => $activateNow ? 'active' : 'pending',
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
            'subscribed_at' => now(),
            'updated_at' => now(),
        ];

        if ($hasConfirm) {
            $payload['confirm_token'] = Str::random(40);
            $payload['confirmed_at'] = $activateNow ? now() : null;
        }

        $isNew = $existing === null;
        $wasInactive = $existing !== null && ($existing->status ?? '') !== 'active';

        if ($existing) {
            $existing->fill($payload)->save();
            $subscriber = $existing;
        } else {
            $subscriber = SiteNewsletterSubscriber::query()->create($payload);
        }

        $this->attachAutomaticLocation($subscriber, $request->ip());

        if ($isNew || $wasInactive) {
            $this->audit->log(
                'newsletter_subscriber',
                'New newsletter subscriber: '.$email.' ('.$source.').',
                null,
                'info',
                ['email' => $email, 'source' => $source],
            );
        }

        return [
            'success' => true,
            'message' => $activateNow
                ? 'Thank you for subscribing!'
                : 'Thank you! Please check your email to confirm your subscription.',
            'created' => $isNew,
            'reactivated' => $wasInactive,
        ];
    }

    private function attachAutomaticLocation(SiteNewsletterSubscriber $subscriber, ?string $ipAddress): void
    {
        if (! Schema::hasColumn('site_newsletter_subscribers', 'location_source')) {
            return;
        }

        $location = $this->locations->resolveFromRequest([], $ipAddress);
        if ($location !== [] && $this->locations->shouldReplace(
            (string) ($subscriber->location_source ?? ''),
            (string) ($location['location_source'] ?? ''),
        )) {
            $subscriber->fill($location)->save();

            return;
        }

        if (trim((string) ($subscriber->country ?? $subscriber->region ?? $subscriber->city ?? '')) === '') {
            BackfillNewsletterSubscriberIpLocationJob::dispatch((int) $subscriber->id);
        }
    }
}
