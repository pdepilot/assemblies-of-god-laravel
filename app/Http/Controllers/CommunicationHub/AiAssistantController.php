<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Http\Requests\CommunicationHub\QueueAiJobRequest;
use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\AiAssistantWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class AiAssistantController
{
    public function __construct(
        private readonly AiAssistantWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        return view('communication-hub.ai-assistant.index', [
            'capabilities' => $this->write->capabilityOptions(),
            'canManage' => $this->policy->manageHub($admin),
            'jobResult' => session('ai_job'),
        ]);
    }

    public function store(QueueAiJobRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        try {
            $job = $this->write->createJob($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['prompt' => $e->getMessage()]);
        }

        return redirect()
            ->route('communication-hub.ai-assistant.index')
            ->with('status', 'AI job queued (architecture only).')
            ->with('ai_job', $job);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
