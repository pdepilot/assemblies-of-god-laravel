<?php

namespace App\Http\Controllers\SundaySchool;

use App\Models\Admin;
use App\Models\SundaySchoolAward;
use App\Services\SundaySchool\CertificateReadService;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\AwardReadService;
use App\Services\SundaySchool\AwardWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AwardsController
{
    public function __construct(
        private readonly AwardReadService $read,
        private readonly AwardWriteService $write,
        private readonly CertificateReadService $certificates,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $status = (string) $request->query('status', '');
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->read->listAwards($status, $page);
        $certificateMap = [];
        foreach ($result['items'] as $item) {
            $cert = $this->certificates->findByAwardId((int) $item['id']);
            if ($cert !== null) {
                $certificateMap[(int) $item['id']] = $cert;
            }
        }

        return view('sunday-school.awards.index', [
            'status' => $status,
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'certificateMap' => $certificateMap,
        ]);
    }

    public function generate(Request $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $request->validate([
            'period_type' => ['required', 'in:monthly,quarterly,annual'],
        ]);

        $result = $this->write->generateRecommendations((string) $request->input('period_type'), (int) $admin->id);

        return redirect()
            ->route('ss.awards.index')
            ->with('status', "{$result['created']} award recommendation(s) created for {$result['period_label']}.");
    }

    public function approve(SundaySchoolAward $award): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $this->write->approveAward((int) $award->id, (int) $admin->id);

        return back()->with('status', 'Award approved.');
    }

    public function reject(Request $request, SundaySchoolAward $award): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $request->validate(['rejection_notes' => ['nullable', 'string']]);
        $this->write->rejectAward((int) $award->id, (int) $admin->id, (string) $request->input('rejection_notes', ''));

        return back()->with('status', 'Award rejected.');
    }

    public function publish(SundaySchoolAward $award): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageClasses($admin);

        $this->write->publishAward((int) $award->id, (int) $admin->id);

        return back()->with('status', 'Award published.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
