<?php

namespace App\Http\Controllers\FinancialErp;

use App\Http\Requests\FinancialErp\SaveVendorRequest;
use App\Models\Admin;
use App\Models\ErpVendor;
use App\Policies\FinancialErpPolicy;
use App\Services\FinancialErp\VendorReadService;
use App\Services\FinancialErp\VendorWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class VendorsController
{
    public function __construct(
        private readonly VendorReadService $read,
        private readonly VendorWriteService $write,
        private readonly FinancialErpPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);

        return view('financial-erp.vendors.index', [
            'items' => $this->read->listVendors(),
            'canManage' => $this->policy->manageErp($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageErp($this->admin());

        return view('financial-erp.vendors.create');
    }

    public function store(SaveVendorRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);

        try {
            $this->write->save($request->validated(), (int) $admin->id, (string) $admin->role);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('financial-erp.vendors.index')->with('status', 'Vendor saved.');
    }

    public function edit(ErpVendor $vendor): View
    {
        $this->policy->requireManageErp($this->admin());

        return view('financial-erp.vendors.edit', ['vendor' => $vendor]);
    }

    public function update(SaveVendorRequest $request, ErpVendor $vendor): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);
        $data = $request->validated();
        $data['id'] = (int) $vendor->id;

        try {
            $this->write->save($data, (int) $admin->id, (string) $admin->role);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('financial-erp.vendors.index')->with('status', 'Vendor updated.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
