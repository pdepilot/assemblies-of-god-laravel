<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\RecipientGroupReadService;
use Illuminate\View\View;

final class RecipientsController
{
    public function __construct(
        private readonly RecipientGroupReadService $read,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        return view('communication-hub.recipients.index', [
            'groups' => $this->read->listGroups(),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
