<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Http\Requests\CommunicationHub\SaveNewsletterDraftRequest;
use App\Models\Admin;
use App\Models\NewsletterDraft;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\NewsletterDraftReadService;
use App\Services\CommunicationHub\NewsletterDraftWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class NewsletterDraftsController
{
    public function __construct(
        private readonly NewsletterDraftReadService $read,
        private readonly NewsletterDraftWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        return view('communication-hub.newsletter-drafts.index', [
            'items' => $this->read->listDrafts(),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageHub($this->admin());

        return view('communication-hub.newsletter-drafts.create', [
            'defaultSections' => NewsletterDraftReadService::defaultSections(),
        ]);
    }

    public function store(SaveNewsletterDraftRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $draft = $this->write->save($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('communication-hub.newsletter-drafts.show', $draft['id'])->with('status', 'Newsletter draft saved.');
    }

    public function show(NewsletterDraft $newsletterDraft): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);
        $row = $this->read->getDraft((int) $newsletterDraft->id);
        abort_if($row === null, 404);

        return view('communication-hub.newsletter-drafts.show', [
            'draft' => $row,
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function edit(NewsletterDraft $newsletterDraft): View
    {
        $this->policy->requireManageHub($this->admin());
        $row = $this->read->getDraft((int) $newsletterDraft->id);
        abort_if($row === null, 404);

        return view('communication-hub.newsletter-drafts.edit', ['draft' => $row]);
    }

    public function update(SaveNewsletterDraftRequest $request, NewsletterDraft $newsletterDraft): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $data = $request->validated();
        $data['id'] = (int) $newsletterDraft->id;

        try {
            $this->write->save($data, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['title' => $e->getMessage()]);
        }

        return redirect()->route('communication-hub.newsletter-drafts.show', $newsletterDraft)->with('status', 'Newsletter draft updated.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
