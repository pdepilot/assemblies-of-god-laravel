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
        $this->validateMemberType($request);

        try {
            $this->write->saveMember(
                $request->only([
                    'full_name', 'role_title', 'bio', 'member_type', 'sort_order', 'is_active',
                ]) + ['site' => 'ag'],
                $request->file('photo'),
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors([$this->errorField($e) => $e->getMessage()]);
        }

        return redirect()->route('website.team.index')->with('status', 'Team member saved.');
    }

    public function edit(SiteTeamMember $member): View|RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());
        if (! $this->isAgMember($member)) {
            return $this->otherSiteRedirect();
        }
        $data = $member->toArray();
        $path = trim((string) ($data['photo_path'] ?? ''));
        $data['photo_url'] = $path !== '' ? $this->assets->url($path) : null;

        return view('website.team.edit', ['member' => $data]);
    }

    public function update(Request $request, SiteTeamMember $member): RedirectResponse
    {
        $this->policy->requireManageWebsite($this->admin());
        if (! $this->isAgMember($member)) {
            return $this->otherSiteRedirect();
        }
        $this->validateMemberType($request);

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
            return back()->withInput()->withErrors([$this->errorField($e) => $e->getMessage()]);
        }

        return redirect()->route('website.team.index')->with('status', 'Team member updated.');
    }

    private function validateMemberType(Request $request): void
    {
        $request->validate([
            'member_type' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (TeamSectionWriteService::normalizeMemberType($value) === null) {
                        $fail('Member type must be Featured Leader or Grid Member.');
                    }
                },
            ],
        ]);
    }

    private function isAgMember(SiteTeamMember $member): bool
    {
        return (string) $member->site === 'ag';
    }

    private function otherSiteRedirect(): RedirectResponse
    {
        return redirect()->route('website.team.index')
            ->withErrors(['member' => 'That team member belongs to another site and cannot be edited here.']);
    }

    private function errorField(InvalidArgumentException $e): string
    {
        $message = $e->getMessage();

        return match (true) {
            str_starts_with($message, 'Member type') => 'member_type',
            str_starts_with($message, 'Photo') => 'photo',
            default => 'full_name',
        };
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
