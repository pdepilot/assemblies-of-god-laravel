<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\SiteNewsletterSubscriber;
use App\Services\Security\SecurityAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class NewsletterSubscribeController extends Controller
{
    public function __construct(
        private readonly SecurityAuditService $audit,
    ) {}

    public function store(Request $request): JsonResponse
    {
        if (trim((string) $request->input('website')) !== '') {
            return response()->json(['success' => true, 'message' => 'Thank you for subscribing.']);
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'source' => ['nullable', 'string', 'max:50'],
        ]);

        $email = strtolower(trim($validated['email']));
        $source = trim((string) ($validated['source'] ?? 'footer')) ?: 'footer';

        $existing = SiteNewsletterSubscriber::query()->where('email', $email)->first();
        if ($existing && ($existing->status ?? '') === 'active') {
            return response()->json([
                'success' => true,
                'message' => 'You are already subscribed. Thank you!',
            ]);
        }

        $hasConfirm = Schema::hasColumn('site_newsletter_subscribers', 'confirm_token');
        // Confirmation email is not wired yet — activate immediately and keep token for later double opt-in.
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
        } else {
            SiteNewsletterSubscriber::query()->create($payload);
        }

        if ($isNew || $wasInactive) {
            $this->audit->log(
                'newsletter_subscriber',
                'New newsletter subscriber: '.$email.' ('.$source.').',
                null,
                'info',
                ['email' => $email, 'source' => $source],
            );
        }

        return response()->json([
            'success' => true,
            'message' => $activateNow
                ? 'Thank you for subscribing!'
                : 'Thank you! Please check your email to confirm your subscription.',
        ]);
    }
}
