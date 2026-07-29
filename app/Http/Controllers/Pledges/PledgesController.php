<?php

namespace App\Http\Controllers\Pledges;

use App\Http\Requests\Pledges\RecordPledgePaymentRequest;
use App\Http\Requests\Pledges\SavePledgeRequest;
use App\Models\Admin;
use App\Models\Pledge;
use App\Policies\DonationPolicy;
use App\Services\Donations\DonationReadService;
use App\Services\Pledges\PledgeReadService;
use App\Services\Pledges\PledgeWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class PledgesController
{
    public function __construct(
        private readonly PledgeReadService $read,
        private readonly PledgeWriteService $write,
        private readonly DonationReadService $donations,
        private readonly DonationPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewDonations($admin);

        $status = (string) $request->query('status', '');
        $query = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 15)));

        $result = $this->read->listPledges($status, $query, $page, $perPage);

        return view('pledges.index', [
            'items' => $result['items'],
            'status' => $status,
            'query' => $query,
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
            'statuses' => PledgeReadService::STATUSES,
            'canManage' => $this->policy->manageDonations($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageDonations($this->admin());

        return view('pledges.create', [
            'categories' => $this->donations->listCategories(),
        ]);
    }

    public function store(SavePledgeRequest $request): RedirectResponse
    {
        $this->policy->requireManageDonations($this->admin());

        try {
            $pledge = $this->write->createPledge($request->validated());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['pledged_amount' => $e->getMessage()]);
        }

        return redirect()->route('pledges.show', $pledge['id'])->with('status', 'Pledge created.');
    }

    public function show(Pledge $pledge): View
    {
        $admin = $this->admin();
        $this->policy->requireViewDonations($admin);

        $data = $this->read->getPledge((int) $pledge->id);
        abort_if($data === null, 404);

        return view('pledges.show', [
            'pledge' => $data,
            'payments' => $this->read->listPayments((int) $pledge->id),
            'canManage' => $this->policy->manageDonations($admin),
        ]);
    }

    public function recordPayment(RecordPledgePaymentRequest $request, Pledge $pledge): RedirectResponse
    {
        $this->policy->requireManageDonations($this->admin());

        try {
            $this->write->recordPayment((int) $pledge->id, (float) $request->validated()['amount']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('pledges.show', $pledge)->with('status', 'Pledge payment recorded.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
