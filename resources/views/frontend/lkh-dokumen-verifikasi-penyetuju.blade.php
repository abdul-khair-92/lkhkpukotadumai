<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Persetujuan LKH</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; padding: 24px; background: #f4f6f9; color: #222; }
        .card { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 24px 28px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        h1 { font-size: 1.15rem; margin: 0 0 8px; color: #198754; }
        .subtitle { font-size: .9rem; color: #666; margin: 0 0 16px; }
        dl { margin: 0; display: grid; grid-template-columns: 150px 1fr; gap: 8px 12px; font-size: .95rem; }
        dt { color: #666; }
        dd { margin: 0; font-weight: 500; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: .8rem; font-weight: 600; }
        .ok { background: #d4edda; color: #155724; }
        .pending { background: #fff3cd; color: #856404; }
        .other { background: #e2e3e5; color: #383d41; }
        .divider { border-top: 1px solid #eee; margin: 16px 0; }
        .section-title { font-size: .85rem; font-weight: 700; color: #444; margin: 0 0 10px; text-transform: uppercase; }
        .note { margin-top: 18px; padding: 12px; background: #d1e7dd; border-radius: 6px; font-size: .88rem; color: #0f5132; }
    </style>
</head>
<body>
<div class="card">
    <h1>Verifikasi Persetujuan LKH</h1>
    <p class="subtitle">QR Penyetuju — konfirmasi atasan yang menyetujui laporan</p>
    <p style="margin-top:0"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></p>

    <p class="section-title">Data penyetuju</p>
    <dl>
        <dt>Nama penyetuju</dt>
        <dd>{{ $penyetuju?->name ?? '—' }}</dd>
        <dt>NIP penyetuju</dt>
        <dd>{{ $penyetuju?->nip ?? '—' }}</dd>
        <dt>Jabatan</dt>
        <dd>{{ $jabatanPenyetuju }}</dd>
        @if(! $menyetujuiTanpaSubbagian && $subbagianPenyetuju && $subbagianPenyetuju !== '—')
            <dt>Subbagian</dt>
            <dd>{{ $subbagianPenyetuju }}</dd>
        @endif
        @if($pengajuan->reviewed_at)
            <dt>Tanggal persetujuan</dt>
            <dd>{{ $pengajuan->reviewed_at->locale('id')->translatedFormat('d F Y, H:i') }} WIB</dd>
        @endif
    </dl>

    <div class="divider"></div>
    <p class="section-title">Laporan yang disetujui</p>
    <dl>
        <dt>Nama pegawai</dt>
        <dd>{{ $pegawai?->name ?? '—' }}</dd>
        <dt>NIP pegawai</dt>
        <dd>{{ $pegawai?->nip ?? '—' }}</dd>
        <dt>Jabatan pegawai</dt>
        <dd>{{ $jabatanPegawai }}</dd>
        <dt>Periode</dt>
        <dd>{{ $bulanLabel }} {{ $pengajuan->tahun }}</dd>
    </dl>

    <p class="note">
        Dokumen ini disetujui secara elektronik oleh penyetuju di atas.
        QR ini khusus untuk memverifikasi identitas <strong>penyetuju / atasan</strong>.
    </p>
</div>
</body>
</html>
