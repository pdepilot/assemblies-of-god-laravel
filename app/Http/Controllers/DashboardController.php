<?php

namespace App\Http\Controllers;

use App\Services\Portal\DashboardReadService;
use Illuminate\View\View;

final class DashboardController
{
    public function __construct(
        private readonly DashboardReadService $dashboard,
    ) {}

    public function index(): View
    {
        return view('dashboard', [
            'stats' => $this->dashboard->summary(),
            'admin' => auth('admin')->user(),
        ]);
    }
}
