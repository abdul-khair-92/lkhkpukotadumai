<?php

namespace App\Http\Controllers\Backend\LkhRekap;

use App\Http\Controllers\Controller;
use App\Services\LkhRekapService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LkhRekapController extends Controller
{
    public function index()
    {
        $list_bulan = collect(range(1, 12))
            ->mapWithKeys(function ($bulan) {
                $monthValue = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

                return [
                    $monthValue => Carbon::createFromFormat('m', $monthValue)->locale('id')->translatedFormat('F'),
                ];
            });

        $tahunBerjalan = (int) now()->format('Y');
        $list_tahun = collect(range($tahunBerjalan - 2, $tahunBerjalan + 1))->sortDesc()->values();

        return view($this->view.'.index', [
            'list_bulan' => $list_bulan,
            'list_tahun' => $list_tahun,
            'filter_default_bulan' => now()->format('m'),
            'filter_default_tahun' => (string) $tahunBerjalan,
        ]);
    }

    public function data(Request $request, LkhRekapService $rekapService, int $bulan, int $tahun)
    {
        $rekap = $rekapService->build($bulan, $tahun, $request->user());

        return view($this->view.'.table', $rekap);
    }

    public function exportPdf(Request $request, LkhRekapService $rekapService, int $bulan, int $tahun)
    {
        $rekap = $rekapService->build($bulan, $tahun, $request->user());

        $pdf = Pdf::loadView('pdf.lkh-rekap', $rekap)
            ->setPaper('a4', 'landscape');

        $filename = sprintf('rekap-lkh-%02d-%d.pdf', $bulan, $tahun);

        return $pdf->download($filename);
    }
}
