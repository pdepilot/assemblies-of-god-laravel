<?php

namespace App\Http\Controllers\Testimonies;

use App\Http\Requests\Testimonies\UpdateSiteTestimonyRequest;
use App\Models\Admin;
use App\Policies\WebsitePolicy;
use App\Services\Testimonies\SiteTestimonyReadService;
use App\Services\Testimonies\SiteTestimonyWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

final class TestimoniesController
{
    public function __construct(
        private readonly SiteTestimonyReadService $read,
        private readonly SiteTestimonyWriteService $write,
        private readonly WebsitePolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewWebsite($admin);

        $status = (string) $request->query('status', 'pending');
        $query = (string) $request->query('q', '');
        $sourcePage = (string) $request->query('source', '');
        $page = max(1, (int) $request->query('page', 1));

        return view('testimonies.index', [
            'result' => $this->read->listTestimonies($status, $query, $sourcePage, $page),
            'stats' => $this->read->getStats(),
            'status' => $status,
            'query' => $query,
            'sourcePage' => $sourcePage,
            'sourcePages' => SiteTestimonyReadService::SOURCE_PAGES,
            'canManage' => $this->policy->manageWebsite($admin),
        ]);
    }

    public function show(int $testimony): View
    {
        $admin = $this->admin();
        $this->policy->requireViewWebsite($admin);
        $row = $this->read->getTestimony($testimony);
        abort_if($row === null, 404);

        return view('testimonies.show', [
            'testimony' => $row,
            'statuses' => SiteTestimonyReadService::STATUSES,
            'canManage' => $this->policy->manageWebsite($admin),
        ]);
    }

    public function update(UpdateSiteTestimonyRequest $request, int $testimony): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);

        try {
            $this->write->updateStatus($testimony, $request->validated(), (int) $admin->id);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('status', 'Testimony updated.');
    }

    public function destroy(int $testimony): RedirectResponse
    {
        $admin = $this->admin();
        $this->policy->requireManageWebsite($admin);

        try {
            $this->write->delete($testimony);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['delete' => $e->getMessage()]);
        }

        return redirect()
            ->route('testimonies.index')
            ->with('status', 'Testimony deleted.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
