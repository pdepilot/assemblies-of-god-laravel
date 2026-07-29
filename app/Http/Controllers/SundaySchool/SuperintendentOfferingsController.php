<?php

namespace App\Http\Controllers\SundaySchool;

use App\Models\Admin;
use App\Policies\SundaySchoolPolicy;
use App\Services\SundaySchool\AnalyticsReadService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SuperintendentOfferingsController
{
    public function __construct(
        private readonly AnalyticsReadService $analyticsRead,
        private readonly SundaySchoolPolicy $policy,
    ) {}

    public function index(Request $request): View
    {
        $admin = $this->admin();
        $this->policy->requireViewAttendanceRemovals($admin);

        $query = (string) $request->query('q', '');
        $from = (string) $request->query('from', now()->startOfYear()->toDateString());
        $to = (string) $request->query('to', now()->toDateString());

        $search = ($query !== '' || $from !== '' || $to !== '')
            ? $this->analyticsRead->searchClassOfferings($query, $from, $to)
            : null;

        return view('sunday-school.superintendent.offerings', [
            'query' => $query,
            'from' => $from,
            'to' => $to,
            'search' => $search,
        ]);
    }

    private function admin(): Admin
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return $admin;
    }
}
