<?php

namespace App\Http\Controllers\Backend\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\LkhDashboardService;

class DashboardController extends Controller
{
    public function index(LkhDashboardService $dashboardService)
    {
        return view($this->view.'.index', [
            'lkhDashboard' => $dashboardService->build(request()->user()),
        ]);
    }
}

