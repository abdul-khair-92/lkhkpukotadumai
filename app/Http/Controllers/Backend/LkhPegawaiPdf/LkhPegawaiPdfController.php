<?php

namespace App\Http\Controllers\Backend\LkhPegawaiPdf;

use App\Http\Controllers\Controller;
use App\Models\LkhPengajuan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class LkhPegawaiPdfController extends Controller
{
    public string $view = 'backend.lkh-pegawai-pdf';

    public function index(Request $request)
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

        $page = (object)[
            'title' => 'Laporan PDF Pegawai',
            'subtitle' => 'Daftar semua laporan PDF yang sudah disetujui',
            'icon' => 'fa fa-file-pdf-o'
        ];

        return view($this->view.'.index', [
            'page' => $page,
            'list_bulan' => $list_bulan,
            'list_tahun' => $list_tahun,
            'filter_default_bulan' => now()->format('m'),
            'filter_default_tahun' => (string) $tahunBerjalan,
        ]);
    }

    public function data(Request $request)
    {
        $bulan = $request->input('bulan');
        $tahun = $request->input('tahun');

        $query = LkhPengajuan::with('user')
            ->where('status', LkhPengajuan::STATUS_APPROVED)
            ->where(function($q) {
                $q->whereNotNull('pdf_path')
                  ->orWhereNotNull('api_archive_id');
            })
            ->orderBy('tahun', 'desc')
            ->orderBy('bulan', 'desc');

        if ($bulan) {
            $query->where('bulan', (int) $bulan);
        }
        if ($tahun) {
            $query->where('tahun', (int) $tahun);
        }

        return datatables()->of($query)
            ->addColumn('user_name', function ($row) {
                return $row->user ? $row->user->name : '-';
            })
            ->addColumn('periode', function ($row) {
                $monthName = Carbon::createFromFormat('m', str_pad((string) $row->bulan, 2, '0', STR_PAD_LEFT))->locale('id')->translatedFormat('F');
                return $monthName . ' ' . $row->tahun;
            })
            ->editColumn('reviewed_at', function ($row) {
                return $row->reviewed_at ? $row->reviewed_at->locale('id')->translatedFormat('d M Y H:i') : '-';
            })
            ->addColumn('action', function ($row) {
                $apiPdfUrl = $row->api_archive_id ? route('lkh-pengajuan.api-pdf', ['id' => $row->id]) : null;
                $pdfUrl = $row->pdf_path && Storage::disk('public')->exists($row->pdf_path)
                    ? route('lkh-pegawai-pdf.view-pdf', ['id' => $row->id])
                    : null;
                
                $targetUrl = $apiPdfUrl ?? $pdfUrl;
                
                if ($targetUrl) {
                    return '<button type="button" class="btn btn-sm btn-info btn-lihat-pdf-laporan" data-url="'.e($targetUrl).'" title="Lihat/Download PDF">
                                <i class="fa fa-file-pdf-o"></i> Lihat PDF
                            </button>';
                }

                return '<span class="text-danger"><small>File tidak ditemukan</small></span>';
            })
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make();
    }

    public function viewPdf(Request $request, string $id)
    {
        $pengajuan = LkhPengajuan::findOrFail($id);

        if (! $pengajuan->pdf_path || ! Storage::disk('public')->exists($pengajuan->pdf_path)) {
            abort(404, 'File PDF lokal tidak ditemukan.');
        }

        $filename = 'LKH-' . $pengajuan->tahun . '-' . str_pad((string) $pengajuan->bulan, 2, '0', STR_PAD_LEFT) . '.pdf';

        return Storage::disk('public')->response($pengajuan->pdf_path, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
