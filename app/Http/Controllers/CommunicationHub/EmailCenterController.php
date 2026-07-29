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
            'result' => $this->read->listHistory($folder, $page, 20),
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
            $parts[] = $result['sent'].' sent';
        }
        if ($result['scheduled'] > 0) {
            $parts[] = $result['scheduled'].' scheduled';
        }
        if ($result['failed'] > 0) {
            $parts[] = $result['failed'].' failed';
        }

        $message = $parts !== []
            ? 'Email processed: '.implode(', ', $parts).'.'
            : 'Email processed.';

        return redirect()
            ->route('communication-hub.email-center.index')
            ->with('status', $message);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
