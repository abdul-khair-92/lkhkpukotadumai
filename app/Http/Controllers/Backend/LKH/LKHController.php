<?php

namespace App\Http\Controllers\Backend\LKH;

use App\Http\Controllers\Controller;
use App\Models\LKH;
use App\Models\LkhPengajuan;
use App\Services\LkhHolidayService;
use App\Services\LkhPengajuanPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class LKHController extends Controller
{
    public function index()
    {
        // Mendapatkan list bulan dan tahun berdasarkan tanggal-tanggal yang sudah diinput user
        $user = request()->user();
        // Ambil semua tanggal dari data LKH user (distinct)
        $dates = $this->model::where('user_id', $user->id)
            ->selectRaw('DISTINCT tanggal')
            ->orderBy('tanggal', 'desc')
            ->pluck('tanggal');

        // Generate list bulan penuh (01-12) untuk filter.
        $list_bulan = collect(range(1, 12))
            ->mapWithKeys(function ($bulan) {
                $monthValue = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

                return [
                    $monthValue => Carbon::createFromFormat('m', $monthValue)->locale('id')->translatedFormat('F'),
                ];
            });

        $bulanBerjalan = now()->format('m');
        $tahunBerjalan = now()->format('Y');

        // Generate list tahun sesuai tanggal yang ada
        $list_tahun = $dates->map(function ($tanggal) {
            return date('Y', strtotime($tanggal));
        })->unique()->values();

        if (! $list_tahun->contains($tahunBerjalan)) {
            $list_tahun->push($tahunBerjalan);
        }
        $list_tahun = $list_tahun->sortDesc()->values();

        view()->share('list_bulan', $list_bulan);
        view()->share('list_tahun', $list_tahun);
        view()->share('filter_default_bulan', $bulanBerjalan);
        view()->share('filter_default_tahun', $tahunBerjalan);
        view()->share('hide_pengajuan_laporan', (int) $user->jabatan === 1);
        view()->share('is_sekretaris', (int) $user->jabatan === 1);

        return view($this->view.'.index');
    }

    public function pickerConfig(LkhHolidayService $holidayService)
    {
        return response()->json([
            'maxDate' => now()->format('Y-m-d'),
            'holidays' => $holidayService->registeredHolidayDates(),
        ]);
    }

    public function generatePdf(Request $request, LkhPengajuanPdfService $pdfService)
    {
        $request->validate([
            'bulan' => 'required',
            'tahun' => 'required|digits:4',
        ]);

        $user = $request->user();

        if ((int) $user->jabatan !== 1) {
            abort(403, 'Fitur ini hanya untuk jabatan Sekretaris.');
        }

        $bulan = (int) $request->input('bulan');
        $tahun = (int) $request->input('tahun');

        $count = LKH::query()
            ->where('user_id', $user->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->count();

        if ($count < 1) {
            abort(404, 'Belum ada data LKH untuk periode yang dipilih.');
        }

        $filename = sprintf('LKH-%04d-%02d.pdf', $tahun, $bulan);

        return $pdfService->makeSekretarisPdf($user, $bulan, $tahun)->download($filename);
    }

    /**
     * Pegawai mengajukan rekapan LKH satu bulan ke atasan.
     */
    public function submitPengajuan(Request $request, LkhPengajuanPdfService $pdfService)
    {
        $request->validate([
            'bulan' => 'required',
            'tahun' => 'required|digits:4',
        ]);

        $user = $request->user();

        if ((int) $user->jabatan === 1) {
            return response()->json([
                'status' => false,
                'message' => 'Pengajuan laporan tidak tersedia untuk jabatan Sekretaris.',
            ]);
        }

        $bulan = (int) $request->input('bulan');
        $tahun = (int) $request->input('tahun');

        if ($bulan < 1 || $bulan > 12) {
            return response()->json(['status' => false, 'message' => 'Bulan tidak valid.']);
        }

        $count = LKH::query()
            ->where('user_id', $user->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->count();

        if ($count < 1) {
            return response()->json([
                'status' => false,
                'message' => 'Belum ada data LKH untuk bulan dan tahun yang dipilih. Isi kegiatan terlebih dahulu.',
            ]);
        }

        $pengajuan = LkhPengajuan::firstOrNew([
            'user_id' => $user->id,
            'bulan' => $bulan,
            'tahun' => $tahun,
        ]);

        $pengajuan->user_id = $user->id;
        $pengajuan->bulan = $bulan;
        $pengajuan->tahun = $tahun;

        if ($pengajuan->exists) {
            if ($pengajuan->status === LkhPengajuan::STATUS_APPROVED) {
                return response()->json([
                    'status' => false,
                    'message' => 'Laporan periode ini sudah disetujui atasan.',
                ]);
            }
            if ($pengajuan->status === LkhPengajuan::STATUS_PENDING) {
                return response()->json([
                    'status' => false,
                    'message' => 'Pengajuan untuk periode ini masih menunggu tinjauan atasan.',
                ]);
            }
        }

        if ($pengajuan->exists && ($pengajuan->pdf_path || $pengajuan->qr_pengaju_token || $pengajuan->qr_penyetuju_token || $pengajuan->document_token)) {
            $pdfService->deleteStoredPdf($pengajuan);
        }

        $pengajuan->status = LkhPengajuan::STATUS_PENDING;
        $pengajuan->catatan_atasan = null;
        $pengajuan->reviewed_by = null;
        $pengajuan->reviewed_at = null;
        $pengajuan->save();

        if (! empty($user->atasan_id)) {
            $this->help::sendNotification($pengajuan, $user->atasan_id, [
                'title' => 'Pengajuan LKH Baru',
                'content' => $user->name.' mengajukan LKH periode '.$this->formatMonthLabel($bulan).' '.$tahun.'.',
                'icon' => 'fa fa-inbox',
                'color' => 'text-warning',
                'link' => route('lkh-pengajuan.index'),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Pengajuan laporan kinerja harian berhasil dikirim ke atasan.',
        ]);
    }

    /**
     * Unduh PDF laporan yang sudah disetujui untuk periode filter (pegawai).
     */
    public function downloadApprovedPdf(Request $request)
    {
        $request->validate([
            'bulan' => 'required',
            'tahun' => 'required|digits:4',
        ]);

        $user = $request->user();
        $pengajuan = LkhPengajuan::query()
            ->where('user_id', $user->id)
            ->where('bulan', (int) $request->input('bulan'))
            ->where('tahun', (int) $request->input('tahun'))
            ->firstOrFail();

        if ($pengajuan->status !== LkhPengajuan::STATUS_APPROVED || ! $pengajuan->pdf_path) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($pengajuan->pdf_path)) {
            abort(404);
        }

        $filename = 'LKH-'.$pengajuan->tahun.'-'.str_pad((string) $pengajuan->bulan, 2, '0', STR_PAD_LEFT).'.pdf';

        return Storage::disk('public')->download($pengajuan->pdf_path, $filename);
    }

    public function create()
    {
        return view($this->view.'.create');
    }

    public function data(Request $request, $bulan = null, $tahun = null)
    {
        $user = $request->user();

        $monthFilter = $request->input('bulan', $bulan);
        $yearFilter = $request->input('tahun', $tahun);

        $query = $this->model::query()->where('user_id', $user->id);
        if ($monthFilter !== null && $monthFilter !== '') {
            $query->whereMonth('tanggal', (int) $monthFilter);
        }
        if ($yearFilter !== null && $yearFilter !== '') {
            $query->whereYear('tanggal', (int) $yearFilter);
        }

        $pengajuanByPeriod = LkhPengajuan::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(fn (LkhPengajuan $p) => $p->tahun.'-'.$p->bulan);

        $pengajuanUi = $this->pengajuanUiPayload($user, $monthFilter, $yearFilter);

        return datatables()->of($query)
            ->addColumn('action', function ($data) use ($user, $pengajuanByPeriod) {
                $t = Carbon::parse($data->tanggal);
                $key = $t->year.'-'.$t->month;
                $p = $pengajuanByPeriod->get($key);
                $editLocked = $p && in_array($p->status, [
                    LkhPengajuan::STATUS_PENDING,
                    LkhPengajuan::STATUS_APPROVED,
                ], true);

                $button = '';
                if ($user->read) {
                    $button .= '<button type="button" class="btn-action btn btn-sm btn-outline" data-title="Detail" data-action="show" data-url="'.$this->url.'" data-id="'.$data->id.'" title="Tampilkan"><i class="fa fa-eye text-info"></i></button>';
                }
                if ($user->update && ! $editLocked) {
                    $button .= '<button type="button" class="btn-action btn btn-sm btn-outline" data-title="Edit" data-action="edit" data-url="'.$this->url.'" data-id="'.$data->id.'" title="Edit"> <i class="fa fa-edit text-warning"></i> </button> ';
                }
                if ($user->delete && ! $editLocked) {
                    $button .= '<button type="button" class="btn-action btn btn-sm btn-outline" data-title="Delete" data-action="delete" data-url="'.$this->url.'" data-id="'.$data->id.'" title="Delete"> <i class="fa fa-trash text-danger"></i> </button>';
                }

                return "<div class='btn-group'>".$button.'</div>';
            })
            ->addIndexColumn()
            ->with('pengajuan_ui', $pengajuanUi)
            ->rawColumns(['action'])
            ->make();
    }

    public function store(Request $request, LkhHolidayService $holidayService)
    {
        // Cek apakah tanggal yang sama untuk user ini sudah ada
        $user = $request->user();
        $tanggal = $request->input('tanggal');
        $exists = $this->model::where('user_id', $user->id)
            ->where('tanggal', $tanggal)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Kegiatan pada tanggal tersebut sudah ada',
            ]);
        }

        $request->validate([
            'tanggal' => 'required|date',
            'kegiatan' => 'required',
            'output' => 'required',
        ]);

        $this->validateSelectableTanggal($request->input('tanggal'), null, $holidayService);

        $p = $this->pengajuanForUserTanggal($user->id, (string) $tanggal);
        if ($this->isLkhPeriodLockedForPengajuan($p)) {
            return response()->json([
                'status' => false,
                'message' => 'Periode bulan kegiatan ini dalam pengajuan atau sudah disetujui; tidak dapat menambah data.',
            ]);
        }

        $payload = array_merge(
            $request->only(['tanggal', 'kegiatan', 'output']),
            ['user_id' => $user->id]
        );

        if ($this->model::create($payload)) {
            $response = ['status' => true, 'message' => 'Data berhasil disimpan'];
        }

        return response()->json($response ?? ['status' => false, 'message' => 'Data gagal disimpan']);
    }

    public function show($id)
    {
        $data = $this->model::where('user_id', request()->user()->id)->findOrFail($id);

        return view($this->view.'.show', compact('data'));
    }

    public function edit($id)
    {
        $user = request()->user();
        $data = $this->model::where('user_id', $user->id)->findOrFail($id);
        $p = $this->pengajuanForUserTanggal($user->id, (string) $data->tanggal);
        if ($this->isLkhPeriodLockedForPengajuan($p)) {
            abort(403, 'Data tidak dapat diubah karena laporan periode ini sedang diajukan atau sudah disetujui.');
        }

        return view($this->view.'.edit', compact('data'));
    }

    public function update(Request $request, $id, LkhHolidayService $holidayService)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'kegiatan' => 'required',
            'output' => 'required',
        ]);

        $user = $request->user();
        $data = $this->model::where('user_id', $user->id)->findOrFail($id);

        $this->validateSelectableTanggal(
            $request->input('tanggal'),
            (string) $data->tanggal,
            $holidayService
        );

        $pOld = $this->pengajuanForUserTanggal($user->id, (string) $data->tanggal);
        if ($this->isLkhPeriodLockedForPengajuan($pOld)) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak dapat diubah karena laporan periode ini sedang diajukan atau sudah disetujui.',
            ]);
        }

        $pNew = $this->pengajuanForUserTanggal($user->id, (string) $request->input('tanggal'));
        if ($this->isLkhPeriodLockedForPengajuan($pNew)) {
            return response()->json([
                'status' => false,
                'message' => 'Tanggal yang dipilih berada pada periode yang sedang diajukan atau sudah disetujui.',
            ]);
        }

        if ($data->update($request->only(['tanggal', 'kegiatan', 'output']))) {
            $response = ['status' => true, 'message' => 'Data berhasil disimpan'];
        }

        return response()->json($response ?? ['status' => false, 'message' => 'Data gagal disimpan']);
    }

    public function delete($id)
    {
        $user = request()->user();
        $data = $this->model::where('user_id', $user->id)->findOrFail($id);
        $p = $this->pengajuanForUserTanggal($user->id, (string) $data->tanggal);
        if ($this->isLkhPeriodLockedForPengajuan($p)) {
            abort(403, 'Data tidak dapat dihapus karena laporan periode ini sedang diajukan atau sudah disetujui.');
        }

        return view($this->view.'.delete', compact('data'));
    }

    public function destroy($id)
    {
        $user = request()->user();
        $data = $this->model::where('user_id', $user->id)->findOrFail($id);
        $p = $this->pengajuanForUserTanggal($user->id, (string) $data->tanggal);
        if ($this->isLkhPeriodLockedForPengajuan($p)) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak dapat dihapus karena laporan periode ini sedang diajukan atau sudah disetujui.',
            ]);
        }

        if ($data->delete()) {
            $response = ['status' => true, 'message' => 'Data berhasil dihapus'];
        }

        return response()->json($response ?? ['status' => false, 'message' => 'Data gagal dihapus']);
    }

    private function pengajuanUiPayload($user, $monthFilter, $yearFilter): array
    {
        $empty = [
            'show_submit' => false,
            'status' => null,
            'status_html' => '<span class="text-muted">Pilih bulan dan tahun, lalu klik Filter untuk melihat status pengajuan.</span>',
            'show_pdf_button' => false,
            'pdf_download_url' => null,
        ];

        if ($monthFilter === null || $monthFilter === '' || $yearFilter === null || $yearFilter === '') {
            return $empty;
        }

        $p = LkhPengajuan::query()
            ->where('user_id', $user->id)
            ->where('bulan', (int) $monthFilter)
            ->where('tahun', (int) $yearFilter)
            ->first();

        $statusHtml = $this->buildPengajuanStatusHtml($p);

        $canSubmit = ! $p || in_array($p->status, [
            LkhPengajuan::STATUS_REVISION,
            LkhPengajuan::STATUS_CANCELLED,
        ], true);

        $isSekretaris = (int) $user->jabatan === 1;

        $showPdf = (bool) $p
            && $p->status === LkhPengajuan::STATUS_APPROVED
            && $p->pdf_path
            && Storage::disk('public')->exists($p->pdf_path);

        $pdfUrl = $showPdf
            ? route('l-k-h.laporan-approved-pdf', ['bulan' => $monthFilter, 'tahun' => $yearFilter])
            : null;

        $hasLkhData = LKH::query()
            ->where('user_id', $user->id)
            ->whereMonth('tanggal', (int) $monthFilter)
            ->whereYear('tanggal', (int) $yearFilter)
            ->exists();

        $generatePdfUrl = $isSekretaris && $hasLkhData
            ? route('l-k-h.generate-pdf', ['bulan' => $monthFilter, 'tahun' => $yearFilter])
            : null;

        return [
            'show_submit' => ! $isSekretaris && (bool) $user->create && $canSubmit,
            'status' => $p?->status,
            'status_html' => $statusHtml,
            'show_pdf_button' => $showPdf && (bool) $user->read,
            'pdf_download_url' => $pdfUrl,
            'show_generate_pdf_button' => $isSekretaris && $hasLkhData && (bool) $user->read,
            'generate_pdf_url' => $generatePdfUrl,
        ];
    }

    private function buildPengajuanStatusHtml(?LkhPengajuan $pengajuan): string
    {
        if (! $pengajuan) {
            return '<span class="text-muted">Belum mengajukan laporan untuk periode ini.</span>';
        }

        $label = match ($pengajuan->status) {
            LkhPengajuan::STATUS_APPROVED => 'Disetujui',
            LkhPengajuan::STATUS_REVISION => 'Revisi',
            LkhPengajuan::STATUS_CANCELLED => 'Dibatalkan',
            LkhPengajuan::STATUS_PENDING => 'Menunggu persetujuan',
            default => $pengajuan->status,
        };

        $class = match ($pengajuan->status) {
            LkhPengajuan::STATUS_APPROVED => 'badge-success',
            LkhPengajuan::STATUS_REVISION => 'badge-danger',
            LkhPengajuan::STATUS_CANCELLED => 'badge-secondary',
            LkhPengajuan::STATUS_PENDING => 'badge-warning',
            default => 'badge-light',
        };

        $catatan = trim((string) ($pengajuan->catatan_atasan ?? ''));
        if ($catatan !== '') {
            return '<span class="badge '.$class.' lkh-status-pengajuan-badge" '
                .'data-toggle="tooltip" data-placement="bottom" data-container="body" '
                .'title="'.e($catatan).'" style="cursor:help;">'
                .e($label).' <i class="fa fa-comment-o" aria-hidden="true"></i></span>';
        }

        return '<span class="badge '.$class.'">'.e($label).'</span>';
    }

    private function validateSelectableTanggal(
        string $tanggal,
        ?string $previousTanggal,
        LkhHolidayService $holidayService
    ): void {
        $date = Carbon::parse($tanggal);
        $except = $previousTanggal ? Carbon::parse($previousTanggal) : null;

        if (! $holidayService->isSelectableTanggal($date, $except)) {
            throw ValidationException::withMessages([
                'tanggal' => [$holidayService->selectableTanggalValidationMessage()],
            ]);
        }
    }

    private function pengajuanForUserTanggal(string $userId, string $tanggal): ?LkhPengajuan
    {
        $t = Carbon::parse($tanggal);

        return LkhPengajuan::query()
            ->where('user_id', $userId)
            ->where('bulan', (int) $t->format('n'))
            ->where('tahun', (int) $t->format('Y'))
            ->first();
    }

    private function isLkhPeriodLockedForPengajuan(?LkhPengajuan $p): bool
    {
        return $p && in_array($p->status, [
            LkhPengajuan::STATUS_PENDING,
            LkhPengajuan::STATUS_APPROVED,
        ], true);
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
