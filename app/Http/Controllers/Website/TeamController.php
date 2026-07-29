<?php

namespace App\Http\Controllers\Website;

use App\Models\Admin;
use App\Models\SiteTeamMember;
use App\Policies\WebsitePolicy;
use App\Services\PublicSite\PublicAssetResolver;
use App\Services\Website\TeamSectionReadService;
use App\Services\Website\TeamSectionWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class TeamController
{
    public function __construct(
        private readonly TeamSectionReadService $read,
        private readonly TeamSectionWriteService $write,
        private readonly WebsitePolicy $policy,
        private readonly PublicAssetResolver $assets,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewWebsite($admin);

        $members = array_map(function (array $member): array {
            $path = trim((string) ($member['photo_path'] ?? ''));
            $member['photo_url'] = $path !== '' ? $this->assets->url($path) : null;

            return $member;
        }, $this->read->listMembers('ag'));

        return view('website.team.index', [
            'members' => $members,
            'canManage' => $this->policy->manageWebsite($admin),
        ]);
    }

    public function create(): View
    {
        $this->policy->requireManageWebsite($this->admin());

        return view('website.team.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());

        try {
            $this->write->saveMember(
                $request->only([
                    'full_name', 'role_title', 'bio', 'member_type', 'sort_order', 'is_active',
                ]) + ['site' => 'ag'],
                $request->file('photo'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('website.team.index')->with('status', 'Team member saved.');
    }

    public function edit(SiteTeamMember $member): View
    {
        $this->policy->requireManageWebsite($this->admin());
        $data = $member->toArray();
        $path = trim((string) ($data['photo_path'] ?? ''));
        $data['photo_url'] = $path !== '' ? $this->assets->url($path) : null;

        return view('website.team.edit', ['member' => $data]);
    }

    public function update(Request $request, SiteTeamMember $member): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());

        $data = $request->only([
            'full_name', 'role_title', 'bio', 'member_type', 'sort_order', 'is_active',
        ]);
        $data['id'] = (int) $member->id;
        $data['site'] = 'ag';

        try {
            $this->write->saveMember(
                $data,
                $request->file('photo'),
                $request->boolean('remove_photo'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['full_name' => $e->getMessage()]);
        }

        return redirect()->route('website.team.index')->with('status', 'Team member updated.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
