<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Http\Requests\CommunicationHub\ComposeEmailRequest;
use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\EmailCenterReadService;
use App\Services\CommunicationHub\EmailCenterWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class EmailCenterController
{
    public function __construct(
        private readonly EmailCenterReadService $read,
        private readonly EmailCenterWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        $folder = (string) $request->query('folder', '');
        $page = max(1, (int) $request->query('page', 1));

        return view('communication-hub.email-center.index', [
            'folder' => $folder,
            'result' => $this->read->listHistory($folder, $page, 15),
            'templates' => $this->read->listEmailTemplates(),
            'recipientGroups' => $this->read->recipientGroups(),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function compose(ComposeEmailRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $result = $this->write->compose($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['compose' => $e->getMessage()]);
        }

        $parts = [];
        if ($result['sent'] > 0) {
            $parts[] = $result['sent'] === 1 ? '1 email sent' : $result['sent'].' emails sent';
        }
        if ($result['scheduled'] > 0) {
            $parts[] = $result['scheduled'] === 1 ? '1 scheduled' : $result['scheduled'].' scheduled';
        }
        if ($result['failed'] > 0) {
            $parts[] = $result['failed'] === 1 ? '1 failed' : $result['failed'].' failed';
        }

        $tone = 'success';
        if ($result['sent'] > 0 && $result['failed'] === 0) {
            $message = $result['sent'] === 1 ? 'Sent.' : 'Sent ('.$result['sent'].').';
            if ($result['scheduled'] > 0) {
                $message = trim($message, '.').' · '.($result['scheduled'] === 1 ? '1 scheduled.' : $result['scheduled'].' scheduled.');
            }
        } elseif ($result['scheduled'] > 0 && $result['sent'] === 0 && $result['failed'] === 0) {
            $message = $result['scheduled'] === 1 ? 'Scheduled.' : 'Scheduled ('.$result['scheduled'].').';
        } elseif ($parts !== []) {
            $message = implode(', ', $parts).'.';
            $tone = $result['failed'] > 0 && $result['sent'] === 0 ? 'error' : 'warning';
        } else {
            $message = 'Email processed.';
            $tone = 'warning';
        }

        return redirect()
            ->route('communication-hub.email-center.index', ['folder' => 'sent'])
            ->with('status', $message)
            ->with('compose_tone', $tone)
            ->with('compose_sent', $result['sent'] > 0);
    }

    public function destroy(Request $request, int $history): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        try {
            $this->write->deleteHistory($history);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['history' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.email-center.index', array_filter([
                'folder' => $request->query('folder'),
                'page' => $request->query('page'),
            ], static fn ($v) => $v !== null && $v !== ''))
            ->with('status', 'Email record deleted.');
    }

    public function destroyMany(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }

        try {
            $deleted = $this->write->deleteHistoryMany($ids);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['history' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.email-center.index', array_filter([
                'folder' => $request->input('folder', $request->query('folder')),
                'page' => $request->input('page', $request->query('page')),
            ], static fn ($v) => $v !== null && $v !== ''))
            ->with('status', $deleted === 1
                ? '1 email record deleted.'
                : $deleted.' email records deleted.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
