<?php

namespace App\Http\Controllers\Commitments;

use App\Http\Requests\Commitments\RecordCommitmentPaymentRequest;
use App\Http\Requests\Commitments\SaveCommitmentGiverRequest;
use App\Http\Requests\Commitments\SaveCommitmentProgramRequest;
use App\Models\Admin;
use App\Models\CommitmentProgram;
use App\Policies\DonationPolicy;
use App\Services\Commitments\CommitmentReadService;
use App\Services\Commitments\CommitmentWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class CommitmentsController
{
    public function __construct(
        private readonly CommitmentReadService $read,
        private readonly CommitmentWriteService $write,
        private readonly DonationPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewDonations($admin);

        $status = (string) $request->query('status', '');

        return view('commitments.index', [
            'programs' => $this->read->listPrograms($status),
            'status' => $status,
            'statuses' => CommitmentReadService::STATUSES,
            'canManage' => $this->policy->manageDonations($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageDonations($this->admin());

        return view('commitments.create', [
            'statuses' => CommitmentReadService::STATUSES,
        ]);
    }

    public function store(SaveCommitmentProgramRequest $request): RedirectResponse
    {
        $this->policy->requireManageDonations($this->admin());

        try {
            $program = $this->write->createProgram($request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('commitments.show', $program['id'])->with('status', 'Commitment program created.');
    }

    public function show(CommitmentProgram $commitment): View
    {
        $admin = $this->admin();
        $this->policy->requireViewDonations($admin);

        $program = $this->read->getProgram((int) $commitment->id);
        abort_if($program === null, 404);

        $givers = $this->read->listGivers((int) $commitment->id, 1, 50);

        return view('commitments.show', [
            'program' => $program,
            'givers' => $givers['items'],
            'frequencies' => CommitmentReadService::FREQUENCIES,
            'canManage' => $this->policy->manageDonations($admin),
        ]);
    }

    public function storeGiver(SaveCommitmentGiverRequest $request, CommitmentProgram $commitment): RedirectResponse
    {
        $this->policy->requireManageDonations($this->admin());

        $data = $request->validated();
        $data['program_id'] = (int) $commitment->id;

        try {
            $this->write->createGiver($data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['donor_name' => $e->getMessage()]);
        }

        return redirect()->route('commitments.show', $commitment)->with('status', 'Commitment giver added.');
    }

    public function recordPayment(RecordCommitmentPaymentRequest $request, int $giver): RedirectResponse
    {
        $this->policy->requireManageDonations($this->admin());

        try {
            $giverRow = $this->write->recordGiverPayment($giver, (float) $request->validated()['amount']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('commitments.show', $giverRow['program_id'])->with('status', 'Payment recorded.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
