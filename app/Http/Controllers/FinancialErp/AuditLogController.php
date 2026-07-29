<?php

namespace App\Http\Controllers\FinancialErp;

use App\Models\Admin;
use App\Policies\FinancialErpPolicy;
use App\Services\FinancialErp\ErpAuditReadService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AuditLogController
{
    public function __construct(
        private readonly ErpAuditReadService $audit,
        private readonly FinancialErpPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewErp($admin);
        $entityType = (string) $request->query('entity_type', '');
        $result = $this->audit->listAuditTrail(100, $entityType !== '' ? $entityType : null);

        return view('financial-erp.audit.index', [
            'items' => $result['items'],
            'entityType' => $entityType,
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
