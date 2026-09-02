<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Http\Requests\Members\MarkMemberDeceasedRequest;
use App\Models\Admin;
use App\Models\Member;
use App\Policies\MemberPolicy;
use App\Services\Members\DeathCertificatePdfService;
use App\Services\Members\MemberReadService;
use App\Services\Members\MemberWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use InvalidArgumentException;

final class DeceasedMembersController extends Controller
{
    public function __construct(
        private readonly MemberReadService $read,
        private readonly MemberWriteService $write,
        private readonly DeathCertificatePdfService $deathCertificates,
        private readonly MemberPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewMembers($admin);

        $query = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->read->listDeceased($query, $page, 20);

        return view('members.deceased', [
            'items' => $result['items'],
            'stats' => $this->read->getDeceasedStats(),
            'query' => $query,
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
            'canManage' => $this->policy->manageMembers($admin),
        ]);
    }

    public function create(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireManageMembers($admin);

        $pickerQuery = trim((string) $request->query('member_q', ''));

        return view('members.deceased-record', [
            'livingMembers' => $this->read->listSelectableLivingMembers($pickerQuery !== '' ? $pickerQuery : null),
            'pickerQuery' => $pickerQuery,
            'statusLabels' => MemberReadService::statusLabels(),
        ]);
    }

    public function store(MarkMemberDeceasedRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageMembers($admin);

        $data = $request->validated();

        try {
            $member = $this->write->markAsDeceased(
                (int) $data['member_id'],
                (string) $data['date_of_death'],
                isset($data['death_notes']) ? (string) $data['death_notes'] : null,
                (int) $admin->id,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['member_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('members.deceased')
            ->with('status', ($member['full_name'] ?? 'Member').' was recorded as deceased.');
    }

    public function certificate(Request $request, Member $member): Response|RedirectResponse
    {
        $this->policy->requireViewMembers($this->admin());

        $data = $this->read->getMember((int) $member->id);
        abort_if($data === null, 404);

        try {
            $pdf = $this->deathCertificates->generate($data);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['certificate' => $e->getMessage()]);
        }

        $disposition = $request->boolean('download') ? 'attachment' : 'inline';

        return response($pdf['binary'], 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$pdf['filename'].'"',
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
