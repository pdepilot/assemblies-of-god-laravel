<?php

namespace App\Http\Controllers\CommunicationHub;

use App\Http\Requests\CommunicationHub\ComposeSmsRequest;
use App\Models\Admin;
use App\Policies\CommunicationHubPolicy;
use App\Services\CommunicationHub\SmsCenterReadService;
use App\Services\CommunicationHub\SmsCenterWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use InvalidArgumentException;

final class SmsCenterController
{
    public function __construct(
        private readonly SmsCenterReadService $read,
        private readonly SmsCenterWriteService $write,
        private readonly CommunicationHubPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewHub($admin);

        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));

        return view('communication-hub.sms-center.index', [
            'status' => $status,
            'board' => $this->read->board($status, $page, 20),
            'templates' => $this->read->smsTemplates(),
            'canManage' => $this->policy->manageHub($admin),
        ]);
    }

    public function compose(ComposeSmsRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageHub($admin);

        $data = $request->validated();
        $templateId = (int) ($data['template_id'] ?? 0);
        if ($templateId > 0 && trim((string) ($data['message'] ?? '')) === '' && Schema::hasTable('communication_templates')) {
            $tpl = DB::table('communication_templates')->where('id', $templateId)->where('channel', 'sms')->first();
            if ($tpl) {
                $data['message'] = (string) ($tpl->body_text ?: $tpl->body_html ?: '');
            }
        }

        try {
            $result = $this->write->compose($data, (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['compose' => $e->getMessage()]);
        }

        $message = match ($result['status']) {
            'sent' => 'SMS sent to '.$result['phone'].' via '.$result['provider'].'.',
            'scheduled' => 'SMS scheduled for '.$result['phone'].'.',
            default => 'SMS failed'.($result['error'] ? ': '.$result['error'] : '.'),
        };

        return redirect()
            ->route('communication-hub.sms-center.index')
            ->with($result['status'] === 'failed' ? 'error' : 'status', $message);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
