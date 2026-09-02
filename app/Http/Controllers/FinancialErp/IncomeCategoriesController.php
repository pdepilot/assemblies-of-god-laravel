<?php

namespace App\Http\Controllers\FinancialErp;

use App\Http\Requests\FinancialErp\SaveIncomeCategoryRequest;
use App\Models\Admin;
use App\Policies\FinancialErpPolicy;
use App\Services\FinancialErp\IncomeReadService;
use App\Services\FinancialErp\IncomeWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use InvalidArgumentException;

final class IncomeCategoriesController
{
    public function __construct(
        private readonly IncomeReadService $read,
        private readonly IncomeWriteService $write,
        private readonly FinancialErpPolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);

        return view('financial-erp.income.categories.index', [
            'items' => $this->read->listCategories(false),
            'canManage' => $this->policy->manageErp($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageErp($this->admin());

        return view('financial-erp.income.categories.create', [
            'accounts' => $this->read->listIncomeAccounts(),
        ]);
    }

    public function store(SaveIncomeCategoryRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);

        try {
            $category = $this->write->createCategory(
                $request->validated(),
                (int) $admin->id,
                (string) $admin->role,
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()
            ->route('financial-erp.income.categories.index')
            ->with('status', 'Category "'.$category['name'].'" added.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
