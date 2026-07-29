<?php

namespace App\Http\Controllers\FinancialErp;

use App\Http\Requests\FinancialErp\SaveExpenseRequest;
use App\Models\Admin;
use App\Models\ErpExpense;
use App\Policies\FinancialErpPolicy;
use App\Services\FinancialErp\ExpenseReadService;
use App\Services\FinancialErp\ExpenseWriteService;
use App\Services\FinancialErp\VendorReadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class ExpensesController
{
    public function __construct(
        private readonly ExpenseReadService $read,
        private readonly ExpenseWriteService $write,
        private readonly VendorReadService $vendors,
        private readonly FinancialErpPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->read->listExpenses($page);

        return view('financial-erp.expenses.index', [
            'items' => $result['items'],
            'canManage' => $this->policy->manageErp($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageErp($this->admin());

        return view('financial-erp.expenses.create', [
            'categories' => $this->read->listCategories(),
            'vendors' => $this->vendors->listVendors(),
        ]);
    }

    public function store(SaveExpenseRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);

        try {
            $expense = $this->write->save($request->validated(), (int) $admin->id, (string) $admin->role);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('financial-erp.expenses.show', $expense['id'])->with('status', 'Expense recorded.');
    }

    public function show(ErpExpense $expense): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);
        $data = $this->read->getExpense((int) $expense->id);
        abort_if($data === null, 404);

        return view('financial-erp.expenses.show', ['expense' => $data]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
