<?php

namespace App\Http\Controllers\Website;

use App\Models\Admin;
use App\Policies\WebsitePolicy;
use App\Services\Website\BlogReadService;
use App\Services\Website\WebsiteMediaIndexReadService;
use Illuminate\View\View;

final class DashboardController
{
    public function __construct(
        private readonly BlogReadService $blog,
        private readonly WebsiteMediaIndexReadService $media,
        private readonly WebsitePolicy $policy,
    ) {}

    public function index(): View
    {
        $admin = $this->admin();
        $this->policy->requireViewWebsite($admin);

        return view('website.dashboard', [
            'blogStats' => $this->blog->getStats(),
            'mediaCounts' => $this->media->getCounts(),
            'canManage' => $this->policy->manageWebsite($admin),
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
