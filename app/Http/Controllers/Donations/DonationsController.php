<?php

namespace App\Http\Controllers\Donations;

use App\Http\Requests\Donations\SaveDonationRequest;
use App\Models\Admin;
use App\Policies\DonationPolicy;
use App\Services\Donations\DonationReadService;
use App\Services\Donations\DonationWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class DonationsController
{
    public function __construct(
        private readonly DonationReadService $read,
        private readonly DonationWriteService $write,
        private readonly DonationPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewDonations($admin);

        $scope = (string) $request->query('scope', 'church');
        $category = (string) $request->query('category', '');
        $query = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(50, (int) $request->query('per_page', 15)));

        $result = $this->read->listDonations($scope, $category, $query, $page, $perPage);

        return view('donations.index', [
            'items' => $result['items'],
            'stats' => $this->read->getStats($scope),
            'categories' => $this->read->listCategories(),
            'scope' => $scope,
            'category' => $category,
            'query' => $query,
            'page' => $result['page'],
            'perPage' => $result['per_page'],
            'total' => $result['total'],
            'totalPages' => $result['pages'],
            'scopes' => DonationReadService::FUND_SCOPES,
            'canManage' => $this->policy->manageDonations($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageDonations($this->admin());

        return view('donations.create', [
            'categories' => $this->read->listCategories(),
            'scopes' => DonationReadService::FUND_SCOPES,
            'paymentMethods' => DonationReadService::PAYMENT_METHODS,
        ]);
    }

    public function store(SaveDonationRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageDonations($admin);

        try {
            $this->write->recordManual($request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('donations.index')->with('status', 'Donation recorded.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
