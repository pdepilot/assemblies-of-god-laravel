<?php

namespace App\Http\Controllers\Contact;

use App\Http\Requests\Contact\ReplyContactSubmissionRequest;
use App\Http\Requests\Contact\UpdateContactSubmissionRequest;
use App\Models\Admin;
use App\Models\ContactSubmission;
use App\Policies\CommunicationHubPolicy;
use App\Services\Contact\ContactSubmissionReadService;
use App\Services\Contact\ContactSubmissionWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

final class SubmissionsController
{
    public function __construct(
        private readonly ContactSubmissionReadService $read,
        private readonly ContactSubmissionWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        $status = (string) $request->query('status', '');
        $type = (string) $request->query('type', '');
        $q = (string) $request->query('q', '');
        $page = max(1, (int) $request->query('page', 1));

        return view('contact.submissions.index', [
            'result' => $this->read->listSubmissions($status, $q, $page, 25, $type),
            'stats' => $this->read->getStats(),
            'statuses' => ContactSubmissionReadService::STATUSES,
            'inquiryTypes' => ContactSubmissionReadService::INQUIRY_TYPES,
            'filters' => ['status' => $status, 'type' => $type, 'q' => $q],
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function show(ContactSubmission $submission): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);
        $row = $this->read->getSubmission((int) $submission->id);
        abort_if($row === null, 404);

        if ($this->policy->manageHub($admin) && ($row['status'] ?? '') === 'new') {
            $this->write->markRead((int) $submission->id, (int) $admin->id);
            $row = $this->read->getSubmission((int) $submission->id) ?? $row;
        }

        return view('contact.submissions.show', [
            'submission' => $row,
            'statuses' => ContactSubmissionReadService::STATUSES,
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function update(UpdateContactSubmissionRequest $request, ContactSubmission $submission): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->update((int) $submission->id, $request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Submission updated.');
    }

    public function reply(ReplyContactSubmissionRequest $request, ContactSubmission $submission): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->reply(
                (int) $submission->id,
                (string) $request->input('reply_subject', ''),
                (string) $request->input('reply_body', ''),
                (int) $admin->id,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['reply_body' => $e->getMessage()])->withInput();
        } catch (RuntimeException $e) {
            return back()->withErrors(['reply_body' => $e->getMessage()])->withInput();
        }

        return back()->with('status', 'Reply emailed to the sender.');
    }

    public function destroy(ContactSubmission $submission): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->delete((int) $submission->id);
        } catch (InvalidArgumentException $e) {
            return redirect()
                ->route('contact.submissions.index')
                ->withErrors(['status' => $e->getMessage()]);
        }

        return redirect()
            ->route('contact.submissions.index')
            ->with('status', 'Message deleted.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
