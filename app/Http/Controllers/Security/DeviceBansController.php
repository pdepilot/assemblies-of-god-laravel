<?php

namespace App\Http\Controllers\Security;

use App\Models\Admin;
use App\Policies\SecurityPolicy;
use App\Services\Security\DeviceBanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class DeviceBansController
{
    public function __construct(
        private readonly DeviceBanService $bans,
        private readonly SecurityPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSecurity($admin);

        $status = (string) $request->query('status', 'active');
        if (! in_array($status, ['active', 'lifted', 'all'], true)) {
            $status = 'active';
        }

        $source = (string) $request->query('source', 'all');
        if ($source !== 'all' && ! in_array($source, DeviceBanService::SOURCES, true)) {
            $source = 'all';
        }

        $result = $this->bans->listBans(
            $status,
            trim((string) $request->query('q', '')),
            max(1, (int) $request->query('page', 1)),
            25,
            $source,
        );

        return view('security.bans.index', [
            'result' => $result,
            'status' => $status,
            'source' => $source,
            'query' => trim((string) $request->query('q', '')),
            'canManage' => $this->policy->manageBans($admin),
            'sources' => DeviceBanService::SOURCES,
        ]);
    }

    public function show(int $ban): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSecurity($admin);
        $row = $this->bans->getBan($ban);
        abort_if($row === null, 404);

        return view('security.bans.show', [
            'ban' => $row,
            'canManage' => $this->policy->manageBans($admin),
        ]);
    }

    public function destroy(int $ban): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageBans($admin);

        if (! $this->bans->liftBan($ban, (int) $admin->id)) {
            return back()->withErrors(['ban' => 'This ban is not active or was already lifted.']);
        }

        return redirect()
            ->route('security.bans.index')
            ->with('status', 'Device ban lifted successfully.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
