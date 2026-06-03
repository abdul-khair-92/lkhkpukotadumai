<?php

namespace App\Services;

use App\Models\LKH;
use App\Models\LkhPengajuan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LkhPengajuanPdfService
{
    public function regenerateAndStore(LkhPengajuan $pengajuan): void
    {
        $pengajuan->loadMissing(['user.atasan', 'reviewer']);

        if (! $pengajuan->qr_pengaju_token) {
            $pengajuan->qr_pengaju_token = Str::random(48);
        }
        if (! $pengajuan->qr_penyetuju_token) {
            $pengajuan->qr_penyetuju_token = Str::random(48);
        }
        $pengajuan->document_token = $pengajuan->qr_penyetuju_token;

        $urlPengaju = route('lkh-dokumen.pengaju', ['token' => $pengajuan->qr_pengaju_token], true);
        $urlPenyetuju = route('lkh-dokumen.penyetuju', ['token' => $pengajuan->qr_penyetuju_token], true);

        $qrPegawaiDataUri = $this->buildQrDataUri($urlPengaju);
        $qrMenyetujuiDataUri = $this->buildQrDataUri($urlPenyetuju);

        $rows = LKH::query()
            ->where('user_id', $pengajuan->user_id)
            ->whereMonth('tanggal', $pengajuan->bulan)
            ->whereYear('tanggal', $pengajuan->tahun)
            ->orderBy('tanggal')
            ->get();

        $pegawai = $pengajuan->user;
        $atasan = $pegawai?->atasan;
        $reviewer = $pengajuan->reviewer;

        $approvedAt = $pengajuan->reviewed_at ?? now();

        $jabatanList = config('master.app.jabatan', []);
        $subbagianList = config('master.app.subbagian', []);

        $jabatanPegawai = $this->labelFromList($jabatanList, $pegawai?->jabatan);
        $subbagianPegawai = $this->labelFromList($subbagianList, $pegawai?->subbagian);
        $jabatanAtasan = $this->labelFromList($jabatanList, $atasan?->jabatan);
        $subbagianAtasan = $this->labelFromList($subbagianList, $atasan?->subbagian);

        $pdf = Pdf::loadView('pdf.lkh-laporan-harian', $this->laporanHarianViewData(
            rows: $rows,
            pegawai: $pegawai,
            atasan: $atasan,
            reviewer: $reviewer,
            pengajuan: $pengajuan,
            qrPegawaiDataUri: $qrPegawaiDataUri,
            qrMenyetujuiDataUri: $qrMenyetujuiDataUri,
            approvedAt: $approvedAt,
            diajukanAt: $pengajuan->created_at ?? $approvedAt,
            hideMenyetujuiAtasan: false,
        ))->setPaper('a4', 'portrait');

        $relativePath = 'lkh_pengajuan/'.$pengajuan->id.'.pdf';
        Storage::disk('public')->put($relativePath, $pdf->output());

        $pengajuan->pdf_path = $relativePath;
        $pengajuan->save();
    }

    /**
     * PDF laporan bulanan untuk Sekretaris (tanpa kolom mengetahui atasan).
     */
    public function makeSekretarisPdf(User $pegawai, int $bulan, int $tahun): \Barryvdh\DomPDF\PDF
    {
        $rows = LKH::query()
            ->where('user_id', $pegawai->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->get();

        $approvedAt = Carbon::create($tahun, $bulan, 1)->endOfMonth();
        if ($approvedAt->isFuture()) {
            $approvedAt = now();
        }

        // Generate QR Code untuk verifikasi dokumen Sekretaris
        $qrToken = Str::random(48);
        $qrUrl = url('/lkh-dokumen/sekretaris/' . $pegawai->id . '/' . $tahun . '/' . $bulan . '?token=' . $qrToken);
        $qrPegawaiDataUri = $this->buildQrDataUri($qrUrl);

        return Pdf::loadView('pdf.lkh-laporan-harian', $this->laporanHarianViewData(
            rows: $rows,
            pegawai: $pegawai,
            atasan: null,
            reviewer: null,
            pengajuan: null,
            qrPegawaiDataUri: $qrPegawaiDataUri,
            qrMenyetujuiDataUri: null,
            approvedAt: $approvedAt,
            diajukanAt: null,
            hideMenyetujuiAtasan: true,
        ))->setPaper('a4', 'portrait');
    }

    /**
     * @param  \Illuminate\Support\Collection<int, LKH>  $rows
     * @return array<string, mixed>
     */
    private function laporanHarianViewData(
        $rows,
        ?User $pegawai,
        ?User $atasan,
        ?User $reviewer,
        ?LkhPengajuan $pengajuan,
        ?string $qrPegawaiDataUri,
        ?string $qrMenyetujuiDataUri,
        Carbon $approvedAt,
        mixed $diajukanAt,
        bool $hideMenyetujuiAtasan,
    ): array {
        $jabatanList = config('master.app.jabatan', []);
        $subbagianList = config('master.app.subbagian', []);

        $jabatanPegawai = $this->labelFromList($jabatanList, $pegawai?->jabatan);
        $subbagianPegawai = $this->labelFromList($subbagianList, $pegawai?->subbagian);
        $jabatanAtasan = $this->labelFromList($jabatanList, $atasan?->jabatan);
        $subbagianAtasan = $this->labelFromList($subbagianList, $atasan?->subbagian);

        $jabatanMenyetujui = $this->labelFromList($jabatanList, $reviewer?->jabatan ?? $atasan?->jabatan);
        $subbagianMenyetujui = $this->labelFromList($subbagianList, $reviewer?->subbagian ?? $atasan?->subbagian);

        return [
            'pengajuan' => $pengajuan,
            'rows' => $rows,
            'pegawai' => $pegawai,
            'atasan' => $atasan,
            'reviewer' => $reviewer,
            'qrPegawaiDataUri' => $qrPegawaiDataUri,
            'qrMenyetujuiDataUri' => $qrMenyetujuiDataUri,
            'diajukanAt' => $diajukanAt,
            'approvedAt' => $approvedAt,
            'jabatanPegawai' => $jabatanPegawai,
            'subbagianPegawai' => $subbagianPegawai,
            'jabatanAtasan' => $jabatanAtasan,
            'subbagianAtasan' => $subbagianAtasan,
            'jabatanMenyetujui' => $jabatanMenyetujui,
            'subbagianMenyetujui' => $subbagianMenyetujui,
            'menyetujuiTanpaSubbagian' => (int) ($reviewer?->jabatan ?? 0) === 1,
            'hideMenyetujuiAtasan' => $hideMenyetujuiAtasan,
            'kotaSurat' => 'Dumai',
        ];
    }

    public function deleteStoredPdf(LkhPengajuan $pengajuan): void
    {
        if ($pengajuan->pdf_path && Storage::disk('public')->exists($pengajuan->pdf_path)) {
            Storage::disk('public')->delete($pengajuan->pdf_path);
        }
        $pengajuan->pdf_path = null;
        $pengajuan->document_token = null;
        $pengajuan->qr_pengaju_token = null;
        $pengajuan->qr_penyetuju_token = null;
        $pengajuan->save();
    }

    private function buildQrDataUri(string $url): string
    {
        return Builder::create()
            ->writer(new PngWriter())
            ->data($url)
            ->size(140)
            ->margin(4)
            ->build()
            ->getDataUri();
    }

    /**
     * Upload the generated PDF file to the KPU API.
     *
     * @throws \Exception
     */
    public function uploadToKpuApi(LkhPengajuan $pengajuan): void
    {
        if (!$pengajuan->pdf_path || !Storage::disk('public')->exists($pengajuan->pdf_path)) {
            throw new \Exception('File PDF tidak ditemukan di storage local.');
        }

        $pegawai = $pengajuan->user;
        $reviewer = $pengajuan->reviewer;

        $subbagianList = config('master.app.subbagian', []);

        $subbagianPegawai = $pegawai ? ($subbagianList[$pegawai->subbagian] ?? $pegawai->subbagian) : '';
        $subbagianReviewer = $reviewer ? ($subbagianList[$reviewer->subbagian] ?? $reviewer->subbagian) : '';

        $bulanLabel = $this->formatMonthLabel((int) $pengajuan->bulan);
        $periode = $bulanLabel . ' ' . $pengajuan->tahun;

        $filename = 'LKH-' . $pengajuan->tahun . '-' . str_pad((string) $pengajuan->bulan, 2, '0', STR_PAD_LEFT) . '.pdf';

        $payload = [
            'appType' => 'bot',
            'uploaderPhone' => '082385417036',
            'caption' => 'LKH ' . ($pegawai?->name ?? '') . ' - ' . $periode,
            'description' => 'Laporan Kinerja Harian ' . ($pegawai?->name ?? '') . ' Periode ' . $periode,
            'docNumber' => $pengajuan->document_token ?? $pengajuan->id,
            'docDate' => $pengajuan->reviewed_at ? $pengajuan->reviewed_at->toIso8601String() : now()->toIso8601String(),
            'unit' => (string) $subbagianPegawai,
            'docKind' => 'LKH',
            'unitSender' => (string) $subbagianPegawai,
            'unitRecipient' => (string) $subbagianReviewer,
            'title' => 'LKH - ' . ($pegawai?->name ?? '') . ' - ' . $periode,
            'subject' => 'Laporan Kinerja Harian',
            'year' => (string) $pengajuan->tahun,
            'category' => 'LKH',
            'sourceType' => 'lkh_app',
            'sourceId' => $pengajuan->id,
            'sourceName' => 'Sistem LKH',
            'senderName' => $pegawai?->name ?? '',
        ];

        $response = Http::withHeaders([
            'X-Integration-Token' => 'itk_6a151911783679b9252e7812.kpJdqyVVAWIDlHHyqdF7e8Ft4RrEj-a1FaXfYavAznU',
        ])->attach(
            'file',
            Storage::disk('public')->get($pengajuan->pdf_path),
            $filename
        )->post('https://serverkpu.fando.id/api/integrations/uploads', $payload);

        if ($response->failed()) {
            $errorMessage = $response->json('message') ?? $response->json('error') ?? $response->body() ?? 'Unknown API Error';
            throw new \Exception('API Error: ' . $errorMessage);
        }
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

    /**
     * @param  array<int|string, string>  $list
     */
    private function labelFromList(array $list, mixed $key): string
    {
        if ($key === null || $key === '') {
            return '—';
        }

        return $list[$key] ?? (string) $key;
    }
}
