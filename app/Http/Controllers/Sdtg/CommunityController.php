<?php

namespace App\Http\Controllers\Sdtg;

use App\Models\Admin;
use App\Policies\SdtgPolicy;
use App\Services\Sdtg\SdtgCommunityReadService;
use App\Services\Sdtg\SdtgCommunityWriteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CommunityController
{
    public function __construct(
        private readonly SdtgCommunityReadService $read,
        private readonly SdtgCommunityWriteService $write,
        private readonly SdtgPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewSdtg($admin);

        return view('sdtg.community.index', [
            'testimonials' => $this->read->listTestimonials((string) $request->query('testimonial_status', '')),
            'prayerRequests' => $this->read->listPrayerRequests((string) $request->query('prayer_status', '')),
            'memories' => $this->read->listMemorySubmissions((string) $request->query('memory_status', '')),
            'canManage' => $this->policy->manageSdtg($admin),
        ]);
    }

    public function updateTestimonial(Request $request, int $testimonial): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $this->write->updateTestimonialStatus($testimonial, (string) $request->input('status', 'pending'));

        return back()->with('status', 'Testimonial updated.');
    }

    public function updatePrayer(Request $request, int $prayer): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $this->write->updatePrayerStatus($prayer, (string) $request->input('status', 'new'));

        return back()->with('status', 'Prayer request updated.');
    }

    public function updateMemory(Request $request, int $memory): RedirectResponse
    {
        $this->policy->requireManageSdtg($this->admin());
        $this->write->updateMemoryStatus($memory, (string) $request->input('status', 'pending'));

        return back()->with('status', 'Memory updated. Featured memories appear on the public gallery.');
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
