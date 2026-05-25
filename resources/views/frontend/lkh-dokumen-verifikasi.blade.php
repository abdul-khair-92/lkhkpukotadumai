<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi dokumen LKH</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; padding: 24px; background: #f4f6f9; color: #222; }
        .card { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 24px 28px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        h1 { font-size: 1.15rem; margin: 0 0 16px; }
        dl { margin: 0; display: grid; grid-template-columns: 140px 1fr; gap: 8px 12px; font-size: .95rem; }
        dt { color: #666; }
        dd { margin: 0; font-weight: 500; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: .8rem; font-weight: 600; }
        .ok { background: #d4edda; color: #155724; }
        .pending { background: #fff3cd; color: #856404; }
        .other { background: #e2e3e5; color: #383d41; }
    </style>
</head>
<body>
<div class="card">
    <h1>Detail dokumen Laporan Kinerja Harian</h1>
    @php
        $status = $pengajuan->status;
        $badgeClass = match ($status) {
            \App\Models\LkhPengajuan::STATUS_APPROVED => 'ok',
            \App\Models\LkhPengajuan::STATUS_PENDING => 'pending',
            default => 'other',
        };
        $statusLabel = match ($status) {
            \App\Models\LkhPengajuan::STATUS_APPROVED => 'Disetujui',
            \App\Models\LkhPengajuan::STATUS_PENDING => 'Menunggu persetujuan',
            \App\Models\LkhPengajuan::STATUS_REVISION => 'Revisi',
            \App\Models\LkhPengajuan::STATUS_CANCELLED => 'Dibatalkan',
            default => $status,
        };
        $bulanLabel = str_pad((string) $pengajuan->bulan, 2, '0', STR_PAD_LEFT);
        try {
            $bulanLabel = \Illuminate\Support\Carbon::createFromFormat('m', $bulanLabel)->locale('id')->translatedFormat('F');
        } catch (\Throwable) {}
    @endphp
    <p style="margin-top:0"><span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span></p>
    <dl>
        <dt>Nama pegawai</dt>
        <dd>{{ $pengajuan->user?->name ?? '—' }}</dd>
        <dt>NIP</dt>
        <dd>{{ $pengajuan->user?->nip ?? '—' }}</dd>
        <dt>Periode</dt>
        <dd>{{ $bulanLabel }} {{ $pengajuan->tahun }}</dd>
        @if($pengajuan->reviewed_at)
            <dt>Disetujui pada</dt>
            <dd>{{ $pengajuan->reviewed_at->locale('id')->translatedFormat('d F Y, H:i') }} WIB</dd>
        @endif
        @if($pengajuan->reviewer)
            <dt>Oleh</dt>
            <dd>{{ $pengajuan->reviewer->name }}</dd>
        @endif
    </dl>
    @if($pengajuan->status === \App\Models\LkhPengajuan::STATUS_APPROVED && $pengajuan->pdf_path)
        <p style="margin-top:20px; font-size:.9rem; color:#555;">Dokumen ini tercatat dalam sistem. File PDF disimpan untuk pegawai yang bersangkutan melalui akun backend.</p>
    @endif
</div>
</body>
</html>
