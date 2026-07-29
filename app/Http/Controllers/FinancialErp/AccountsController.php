<?php

namespace App\Http\Controllers\FinancialErp;

use App\Http\Requests\FinancialErp\SaveAccountRequest;
use App\Models\Admin;
use App\Models\ErpAccount;
use App\Policies\FinancialErpPolicy;
use App\Services\FinancialErp\AccountReadService;
use App\Services\FinancialErp\AccountWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class AccountsController
{
    public function __construct(
        private readonly AccountReadService $read,
        private readonly AccountWriteService $write,
        private readonly FinancialErpPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);
        $type = (string) $request->query('type', '');

        return view('financial-erp.accounts.index', [
            'items' => $this->read->listAccounts($type !== '' ? $type : null),
            'type' => $type,
            'types' => AccountReadService::ACCOUNT_TYPES,
            'canManage' => $this->policy->manageErp($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageErp($this->admin());

        return view('financial-erp.accounts.create', ['types' => AccountReadService::ACCOUNT_TYPES]);
    }

    public function store(SaveAccountRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);

        try {
            $this->write->save($request->validated(), (int) $admin->id, (string) $admin->role);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        }

        return redirect()->route('financial-erp.accounts.index')->with('status', 'Account saved.');
    }

    public function edit(ErpAccount $account): View
    {
        $this->policy->requireManageErp($this->admin());

        return view('financial-erp.accounts.edit', [
            'account' => $account,
            'types' => AccountReadService::ACCOUNT_TYPES,
        ]);
    }

    public function update(SaveAccountRequest $request, ErpAccount $account): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);

        $data = $request->validated();
        $data['id'] = (int) $account->id;

        try {
            $this->write->save($data, (int) $admin->id, (string) $admin->role);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['code' => $e->getMessage()]);
        }

        return redirect()->route('financial-erp.accounts.index')->with('status', 'Account updated.');
    }

    public function destroy(ErpAccount $account): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);

        try {
            $this->write->softDelete((int) $account->id, (int) $admin->id, (string) $admin->role);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return redirect()->route('financial-erp.accounts.index')->with('status', 'Account archived.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
