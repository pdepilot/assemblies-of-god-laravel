<?php

namespace App\Http\Controllers\FinancialErp;

use App\Http\Requests\FinancialErp\SaveIncomeRequest;
use App\Models\Admin;
use App\Models\ErpIncome;
use App\Policies\FinancialErpPolicy;
use App\Services\FinancialErp\IncomeReadService;
use App\Services\FinancialErp\IncomeWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class IncomeController
{
    public function __construct(
        private readonly IncomeReadService $read,
        private readonly IncomeWriteService $write,
        private readonly FinancialErpPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);
        $category = (string) $request->query('category', '');
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->read->listIncome($page, 25, $category !== '' ? $category : null);

        return view('financial-erp.income.index', [
            'items' => $result['items'],
            'totalAmount' => $result['total_amount'],
            'category' => $category,
            'categories' => $this->read->listCategories(),
            'canManage' => $this->policy->manageErp($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageErp($this->admin());

        return view('financial-erp.income.create', [
            'categories' => $this->read->listCategories(),
        ]);
    }

    public function store(SaveIncomeRequest $request): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageErp($admin);

        try {
            $income = $this->write->save($request->validated(), (int) $admin->id, (string) $admin->role);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        return redirect()->route('financial-erp.income.show', $income['id'])->with('status', 'Income recorded.');
    }

    public function show(ErpIncome $income): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);
        $data = $this->read->getIncome((int) $income->id);
        abort_if($data === null, 404);

        return view('financial-erp.income.show', ['income' => $data]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
