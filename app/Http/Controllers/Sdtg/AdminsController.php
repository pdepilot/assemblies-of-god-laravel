<?php

namespace App\Http\Controllers\Sdtg;

use App\Http\Controllers\Concerns\ResolvesSdtgAdmin;
use App\Models\Admin;
use App\Policies\SdtgPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminsController
{
    use ResolvesSdtgAdmin;

    public function __construct(
        private readonly SdtgPolicy $policy,
    ) {}

    public function index(): View
    {
        $this->policy->requireManageSdtg($this->admin());

        $admins = Admin::query()
            ->orderBy('full_name')
            ->orderBy('email')
            ->get([
                'id',
                'email',
                'full_name',
                'role',
                'is_active',
                'account_status',
                'platform_access',
            ]);

        return view('sdtg.admins.index', [
            'admins' => $admins,
            'platformOptions' => Admin::PLATFORM_ACCESS_VALUES,
        ]);
    }

    public function updatePlatformAccess(Request $request, int $admin): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());

        $validated = $request->validate([
            'platform_access' => ['required', 'in:'.implode(',', Admin::PLATFORM_ACCESS_VALUES)],
        ]);

        $target = Admin::query()->findOrFail($admin);
        $target->platform_access = $validated['platform_access'];
        $target->updated_at = now();
        $target->save();

        return back()->with('status', 'Platform access updated for '.$target->email.'.');
    }
}
