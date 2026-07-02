<?php

namespace App\Http\Controllers\Backend\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\LkhDashboardService;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request, LkhDashboardService $dashboardService)
    {
        $bulan = $request->input('bulan', now()->format('m'));
        $tahun = $request->input('tahun', now()->format('Y'));

        $list_bulan = collect(range(1, 12))
            ->mapWithKeys(function ($b) {
                $monthValue = str_pad((string) $b, 2, '0', STR_PAD_LEFT);
                return [
                    $monthValue => \Illuminate\Support\Carbon::createFromFormat('m', $monthValue)->locale('id')->translatedFormat('F'),
                ];
            });

        $tahunBerjalan = (int) now()->format('Y');
        $list_tahun = collect(range($tahunBerjalan - 2, $tahunBerjalan + 1))->sortDesc()->values();

        return view($this->view.'.index', [
            'lkhDashboard' => $dashboardService->build($request->user(), (int) $bulan, (int) $tahun),
            'list_bulan' => $list_bulan,
            'list_tahun' => $list_tahun,
            'filter_bulan' => str_pad($bulan, 2, '0', STR_PAD_LEFT),
            'filter_tahun' => $tahun,
        ]);
    }
}

