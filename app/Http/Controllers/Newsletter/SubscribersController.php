<?php

namespace App\Http\Controllers\Newsletter;

use App\Http\Requests\Newsletter\SendNewsletterRequest;
use App\Models\Admin;
use App\Models\SiteNewsletterSubscriber;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\TemplateReadService;
use App\Services\Newsletter\SubscriberReadService;
use App\Services\Newsletter\SubscriberWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class SubscribersController
{
    public function __construct(
        private readonly SubscriberReadService $read,
        private readonly SubscriberWriteService $write,
        private readonly TemplateReadService $templates,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        $filters = [
            'q' => (string) $request->query('q', ''),
            'status' => (string) $request->query('status', ''),
            'source' => (string) $request->query('source', ''),
        ];

        return view('newsletter.subscribers.index', [
            'result' => $this->read->listSubscribers(
                $filters['q'],
                $filters['status'],
                $filters['source'],
                max(1, (int) $request->query('page', 1)),
            ),
            'stats' => $this->read->getStats(),
            'filters' => $filters,
            'templates' => $this->templates->listTemplates('email', 'published'),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function show(SiteNewsletterSubscriber $subscriber): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);
        $row = $this->read->getSubscriber((int) $subscriber->id);
        abort_if($row === null, 404);

        return view('newsletter.subscribers.show', [
            'subscriber' => $row,
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function toggleStatus(SiteNewsletterSubscriber $subscriber): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $current = $this->read->getSubscriber((int) $subscriber->id);
        abort_if($current === null, 404);

        $newStatus = ($current['status'] ?? '') === 'active' ? 'unsubscribed' : 'active';

        try {
            $this->write->setStatus((int) $subscriber->id, $newStatus);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Subscriber status updated.');
    }

    public function destroy(SiteNewsletterSubscriber $subscriber): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->delete((int) $subscriber->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('newsletter-subscribers.index')
            ->with('status', 'Subscriber deleted.');
    }

    public function send(SendNewsletterRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $data = $request->validated();
        $audience = (string) $data['audience'];
        $subject = trim((string) ($data['subject'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));
        $templateId = (int) ($data['template_id'] ?? 0);

        try {
            $result = $audience === 'all_active'
                ? $this->write->sendToAllActive($subject, $body, (int) $admin->id, $templateId)
                : $this->write->sendToSelected(
                    $data['subscriber_ids'] ?? [],
                    $subject,
                    $body,
                    (int) $admin->id,
                    $templateId,
                );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['body' => $e->getMessage()])->withInput();
        }

        $message = 'Newsletter sent to '.(int) ($result['sent'] ?? 0).' subscriber(s)';
        if (! empty($result['failed'])) {
            $message .= ' ('.(int) $result['failed'].' failed)';
        }
        $message .= '.';

        return back()->with('status', $message);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
