<?php

namespace App\Http\Controllers\FinancialErp;

use App\Models\Admin;
use App\Policies\FinancialErpPolicy;
use Illuminate\Http\RedirectResponse;

/**
 * Church CMS → Financial ERP login (Laravel /erp/login → legacy ERP via proxy).
 */
final class LaunchController
{
    public function __construct(
        private readonly FinancialErpPolicy $policy,
    ) {}

    public function __invoke(): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();
        $this->policy->requireViewErp($admin);

        return redirect()->to(url('/erp/login?from=portal'));
    }
}
