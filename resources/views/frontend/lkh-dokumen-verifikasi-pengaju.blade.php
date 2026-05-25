<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Pengajuan LKH</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; margin: 0; padding: 24px; background: #f4f6f9; color: #222; }
        .card { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 8px; padding: 24px 28px; box-shadow: 0 1px 4px rgba(0,0,0,.08); }
        h1 { font-size: 1.15rem; margin: 0 0 8px; color: #0d6efd; }
        .subtitle { font-size: .9rem; color: #666; margin: 0 0 16px; }
        dl { margin: 0; display: grid; grid-template-columns: 150px 1fr; gap: 8px 12px; font-size: .95rem; }
        dt { color: #666; }
        dd { margin: 0; font-weight: 500; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: .8rem; font-weight: 600; }
        .ok { background: #d4edda; color: #155724; }
        .pending { background: #fff3cd; color: #856404; }
        .other { background: #e2e3e5; color: #383d41; }
        .note { margin-top: 18px; padding: 12px; background: #e7f1ff; border-radius: 6px; font-size: .88rem; color: #084298; }
    </style>
</head>
<body>
<div class="card">
    <h1>Verifikasi Pengajuan LKH</h1>
    <p class="subtitle">QR Pengaju — konfirmasi data pegawai yang mengajukan laporan</p>
    <p style="margin-top:0"><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></p>
    <dl>
        <dt>Nama pengaju</dt>
        <dd>{{ $pegawai?->name ?? '—' }}</dd>
        <dt>NIP</dt>
        <dd>{{ $pegawai?->nip ?? '—' }}</dd>
        <dt>Jabatan</dt>
        <dd>{{ $jabatanPegawai }}</dd>
        <dt>Subbagian</dt>
        <dd>{{ $subbagianPegawai }}</dd>
        <dt>Periode laporan</dt>
        <dd>{{ $bulanLabel }} {{ $pengajuan->tahun }}</dd>
        <dt>Jumlah kegiatan</dt>
        <dd>{{ $jumlahKegiatan }} hari terisi</dd>
        <dt>Tanggal pengajuan</dt>
        <dd>{{ $pengajuan->created_at?->locale('id')->translatedFormat('d F Y, H:i') ?? '—' }} WIB</dd>
    </dl>
    <p class="note">
        Dokumen ini diajukan secara elektronik oleh pegawai di atas.
        QR ini khusus untuk memverifikasi identitas <strong>pengaju</strong> LKH.
    </p>
</div>
</body>
</html>
