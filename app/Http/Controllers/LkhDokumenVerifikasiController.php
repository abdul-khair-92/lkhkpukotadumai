<?php

namespace App\Http\Controllers;

use App\Models\LKH;
use App\Models\LkhPengajuan;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LkhDokumenVerifikasiController extends Controller
{
    /**
     * Legacy route — mencoba token lama lalu token pengaju/penyetuju.
     */
    public function show(string $token): View
    {
        $pengajuan = LkhPengajuan::query()
            ->where(function ($q) use ($token) {
                $q->where('document_token', $token)
                    ->orWhere('qr_pengaju_token', $token)
                    ->orWhere('qr_penyetuju_token', $token);
            })
            ->with(['user', 'reviewer'])
            ->firstOrFail();

        if ($pengajuan->qr_penyetuju_token === $token) {
            return $this->renderPenyetuju($pengajuan);
        }

        return $this->renderPengaju($pengajuan);
    }

    public function pengaju(string $token): View
    {
        $pengajuan = $this->findByPengajuToken($token);

        return $this->renderPengaju($pengajuan);
    }

    public function penyetuju(string $token): View
    {
        $pengajuan = $this->findByPenyetujuToken($token);

        return $this->renderPenyetuju($pengajuan);
    }

    private function findByPengajuToken(string $token): LkhPengajuan
    {
        return LkhPengajuan::query()
            ->where('qr_pengaju_token', $token)
            ->with(['user'])
            ->firstOrFail();
    }

    private function findByPenyetujuToken(string $token): LkhPengajuan
    {
        return LkhPengajuan::query()
            ->where('qr_penyetuju_token', $token)
            ->with(['user', 'reviewer'])
            ->firstOrFail();
    }

    private function renderPengaju(LkhPengajuan $pengajuan): View
    {
        $pengajuan->loadMissing('user');

        $jabatanList = config('master.app.jabatan', []);
        $subbagianList = config('master.app.subbagian', []);

        $jumlahKegiatan = LKH::query()
            ->where('user_id', $pengajuan->user_id)
            ->whereMonth('tanggal', $pengajuan->bulan)
            ->whereYear('tanggal', $pengajuan->tahun)
            ->count();

        return view('frontend.lkh-dokumen-verifikasi-pengaju', [
            'pengajuan' => $pengajuan,
            'pegawai' => $pengajuan->user,
            'bulanLabel' => $this->bulanLabel($pengajuan->bulan),
            'jabatanPegawai' => $this->labelFromList($jabatanList, $pengajuan->user?->jabatan),
            'subbagianPegawai' => $this->labelFromList($subbagianList, $pengajuan->user?->subbagian),
            'jumlahKegiatan' => $jumlahKegiatan,
            'statusLabel' => $this->statusLabel($pengajuan->status),
            'statusClass' => $this->statusClass($pengajuan->status),
        ]);
    }

    private function renderPenyetuju(LkhPengajuan $pengajuan): View
    {
        $pengajuan->loadMissing(['user', 'reviewer']);

        $jabatanList = config('master.app.jabatan', []);
        $subbagianList = config('master.app.subbagian', []);

        $penyetuju = $pengajuan->reviewer;

        return view('frontend.lkh-dokumen-verifikasi-penyetuju', [
            'pengajuan' => $pengajuan,
            'pegawai' => $pengajuan->user,
            'penyetuju' => $penyetuju,
            'bulanLabel' => $this->bulanLabel($pengajuan->bulan),
            'jabatanPegawai' => $this->labelFromList($jabatanList, $pengajuan->user?->jabatan),
            'jabatanPenyetuju' => $this->labelFromList($jabatanList, $penyetuju?->jabatan),
            'subbagianPenyetuju' => $this->labelFromList($subbagianList, $penyetuju?->subbagian),
            'menyetujuiTanpaSubbagian' => (int) ($penyetuju?->jabatan ?? 0) === 1,
            'statusLabel' => $this->statusLabel($pengajuan->status),
            'statusClass' => $this->statusClass($pengajuan->status),
        ]);
    }

    private function bulanLabel(int $bulan): string
    {
        try {
            return Carbon::createFromFormat('m', str_pad((string) $bulan, 2, '0', STR_PAD_LEFT))
                ->locale('id')
                ->translatedFormat('F');
        } catch (\Throwable) {
            return (string) $bulan;
        }
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            LkhPengajuan::STATUS_APPROVED => 'Disetujui',
            LkhPengajuan::STATUS_PENDING => 'Menunggu persetujuan',
            LkhPengajuan::STATUS_REVISION => 'Revisi',
            LkhPengajuan::STATUS_CANCELLED => 'Dibatalkan',
            default => $status,
        };
    }

    private function statusClass(string $status): string
    {
        return match ($status) {
            LkhPengajuan::STATUS_APPROVED => 'ok',
            LkhPengajuan::STATUS_PENDING => 'pending',
            default => 'other',
        };
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
