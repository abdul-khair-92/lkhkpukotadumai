<?php

namespace App\Http\Controllers\Backend\LkhPengajuan;

use App\Http\Controllers\Controller;
use App\Models\LKH;
use App\Models\LkhPengajuan;
use App\Services\LkhPengajuanPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LkhPengajuanController extends Controller
{
    public function index()
    {
        return view($this->view.'.index');
    }

    public function data(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('access_group');

        $query = LkhPengajuan::query()
            ->with(['user', 'reviewer'])
            ->orderByDesc('tahun')
            ->orderByDesc('bulan')
            ->orderByDesc('created_at');

        if (! in_array($user->access_group->code ?? '', ['root', 'admin'], true)) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('atasan_id', $user->id);
            });
        }

        return datatables()->of($query)
            ->filterColumn('nama_pegawai', function ($query, $keyword) {
                $query->whereHas('user', function ($q) use ($keyword) {
                    $q->whereRaw('(CONCAT(COALESCE(first_name,\'\'),\' \',COALESCE(last_name,\'\'))) like ?', ["%{$keyword}%"]);
                });
            })
            ->addColumn('nama_pegawai', fn ($row) => e($row->user?->name ?? '-'))
            ->editColumn('bulan', function ($row) {
                $m = str_pad((string) $row->bulan, 2, '0', STR_PAD_LEFT);

                try {
                    return Carbon::createFromFormat('m', $m)->locale('id')->translatedFormat('F');
                } catch (\Throwable) {
                    return (string) $row->bulan;
                }
            })
            ->addColumn('status_label', function ($row) {
                return match ($row->status) {
                    LkhPengajuan::STATUS_APPROVED => '<span class="badge badge-success">Disetujui</span>',
                    LkhPengajuan::STATUS_REVISION => '<span class="badge badge-danger">Revisi</span>',
                    LkhPengajuan::STATUS_CANCELLED => '<span class="badge badge-secondary">Dibatalkan</span>',
                    default => '<span class="badge badge-warning">Menunggu persetujuan</span>',
                };
            })
            ->editColumn('created_at', fn ($row) => $row->created_at
                ? $row->created_at->locale('id')->translatedFormat('d M Y H:i')
                : '—')
            ->addColumn('reviewed_info', function ($row) {
                if (! $row->reviewed_at) {
                    return '<span class="text-muted">—</span>';
                }
                $oleh = $row->reviewer?->name ?? '—';

                return '<div><small>'.e($row->reviewed_at->locale('id')->translatedFormat('d M Y H:i')).'</small><br><small>Oleh: '.e($oleh).'</small></div>';
            })
            ->addColumn('action', function ($row) use ($user) {
                $id = $row->id;
                $btn = '';
                if ($user->read) {
                    $btn .= '<button type="button" class="btn btn-sm btn-outline btn-lihat-laporan" data-id="'.e($id).'" title="Lihat kegiatan"><i class="fa fa-list-alt text-primary"></i></button> ';
                }
                if ($user->update && $row->status === LkhPengajuan::STATUS_PENDING) {
                    $btn .= '<button type="button" class="btn btn-sm btn-outline btn-approve-pengajuan" data-id="'.e($id).'" title="Setujui"><i class="fa fa-check text-success"></i></button> ';
                    $btn .= '<button type="button" class="btn btn-sm btn-outline btn-revisi-pengajuan" data-id="'.e($id).'" title="Revisi"><i class="fa fa-undo text-warning"></i></button>';
                }
                if ($user->update && $row->status === LkhPengajuan::STATUS_APPROVED) {
                    $btn .= '<button type="button" class="btn btn-sm btn-outline btn-batalkan-pengajuan" data-id="'.e($id).'" title="Batalkan persetujuan"><i class="fa fa-ban text-secondary"></i></button>';
                }

                return "<div class='btn-group'>".$btn.'</div>';
            })
            ->rawColumns(['status_label', 'reviewed_info', 'action'])
            ->addIndexColumn()
            ->make();
    }

    public function laporan(Request $request, string $id)
    {
        $pengajuan = LkhPengajuan::with('user')->findOrFail($id);
        $this->authorizePimpinan($request->user(), $pengajuan);

        $rows = LKH::query()
            ->where('user_id', $pengajuan->user_id)
            ->whereMonth('tanggal', $pengajuan->bulan)
            ->whereYear('tanggal', $pengajuan->tahun)
            ->orderBy('tanggal')
            ->get();

        return view($this->view.'.laporan', compact('pengajuan', 'rows'));
    }

    public function approve(Request $request, string $id, LkhPengajuanPdfService $pdfService)
    {
        $pengajuan = LkhPengajuan::findOrFail($id);
        $this->authorizePimpinan($request->user(), $pengajuan);

        if ($pengajuan->status !== LkhPengajuan::STATUS_PENDING) {
            return response()->json(['status' => false, 'message' => 'Pengajuan sudah diproses sebelumnya.']);
        }

        try {
            DB::transaction(function () use ($request, $pengajuan, $pdfService) {
                $pengajuan->status = LkhPengajuan::STATUS_APPROVED;
                $pengajuan->catatan_atasan = null;
                $pengajuan->reviewed_by = $request->user()->id;
                $pengajuan->reviewed_at = now();
                $pengajuan->save();
                $pdfService->regenerateAndStore($pengajuan->fresh());
            });
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => false,
                'message' => 'Persetujuan gagal menyimpan PDF. Silakan coba lagi atau hubungi administrator.',
            ], 500);
        }

        $this->notifyPegawai($pengajuan->fresh(['user']), 'approved');

        return response()->json(['status' => true, 'message' => 'Laporan disetujui dan PDF telah dibuat.']);
    }

    public function revisi(Request $request, string $id)
    {
        $request->validate([
            'catatan_atasan' => 'required|string|max:5000',
        ]);

        $pengajuan = LkhPengajuan::findOrFail($id);
        $this->authorizePimpinan($request->user(), $pengajuan);

        if ($pengajuan->status !== LkhPengajuan::STATUS_PENDING) {
            return response()->json(['status' => false, 'message' => 'Pengajuan sudah diproses sebelumnya.']);
        }

        $pengajuan->status = LkhPengajuan::STATUS_REVISION;
        $pengajuan->catatan_atasan = $request->input('catatan_atasan');
        $pengajuan->reviewed_by = $request->user()->id;
        $pengajuan->reviewed_at = now();
        $pengajuan->save();

        $this->notifyPegawai($pengajuan->fresh(['user']), 'revision');

        return response()->json(['status' => true, 'message' => 'Catatan revisi dikirim ke pegawai.']);
    }

    public function batalkan(Request $request, string $id, LkhPengajuanPdfService $pdfService)
    {
        $pengajuan = LkhPengajuan::findOrFail($id);
        $this->authorizePimpinan($request->user(), $pengajuan);

        if ($pengajuan->status !== LkhPengajuan::STATUS_APPROVED) {
            return response()->json([
                'status' => false,
                'message' => 'Batalkan persetujuan hanya untuk laporan yang sudah disetujui.',
            ]);
        }

        $pdfService->deleteStoredPdf($pengajuan);

        $pengajuan->status = LkhPengajuan::STATUS_CANCELLED;
        $pengajuan->catatan_atasan = null;
        $pengajuan->reviewed_by = $request->user()->id;
        $pengajuan->reviewed_at = now();
        $pengajuan->save();

        $this->notifyPegawai($pengajuan->fresh(['user']), 'cancelled');

        return response()->json([
            'status' => true,
            'message' => 'Persetujuan dibatalkan. Pegawai dapat memperbarui LKH dan mengajukan ulang.',
        ]);
    }

    private function authorizePimpinan($authUser, LkhPengajuan $pengajuan): void
    {
        $authUser->loadMissing('access_group');
        if (in_array($authUser->access_group->code ?? '', ['root', 'admin'], true)) {
            return;
        }

        $pegawai = $pengajuan->user;
        if (! $pegawai || $pegawai->atasan_id !== $authUser->id) {
            abort(403, 'Anda tidak berhak mengakses pengajuan ini.');
        }
    }

    private function notifyPegawai(LkhPengajuan $pengajuan, string $event): void
    {
        if (! $pengajuan->user_id) {
            return;
        }

        $bulanLabel = $this->formatMonthLabel((int) $pengajuan->bulan);
        $periode = $bulanLabel.' '.$pengajuan->tahun;

        [$title, $content, $color, $icon] = match ($event) {
            'approved' => [
                'Laporan LKH Disetujui',
                'Laporan LKH periode '.$periode.' telah disetujui atasan.',
                'text-success',
                'fa fa-check-circle',
            ],
            'revision' => [
                'Laporan LKH Perlu Revisi',
                'Laporan LKH periode '.$periode.' diminta revisi oleh atasan.',
                'text-warning',
                'fa fa-undo',
            ],
            'cancelled' => [
                'Persetujuan LKH Dibatalkan',
                'Persetujuan LKH periode '.$periode.' dibatalkan. Anda dapat perbarui data dan ajukan ulang.',
                'text-secondary',
                'fa fa-ban',
            ],
            default => ['Notifikasi LKH', 'Ada pembaruan pada pengajuan LKH Anda.', 'text-info', 'fa fa-bell'],
        };

        $this->help::sendNotification($pengajuan, $pengajuan->user_id, [
            'title' => $title,
            'content' => $content,
            'icon' => $icon,
            'color' => $color,
            'link' => route('l-k-h.index'),
        ]);
    }

    private function formatMonthLabel(int $bulan): string
    {
        try {
            return Carbon::createFromFormat('m', str_pad((string) $bulan, 2, '0', STR_PAD_LEFT))
                ->locale('id')
                ->translatedFormat('F');
        } catch (\Throwable) {
            return (string) $bulan;
        }
    }
}
