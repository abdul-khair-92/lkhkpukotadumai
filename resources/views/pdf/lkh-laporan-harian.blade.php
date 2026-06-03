<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #111; }
        .title { text-align: center; font-weight: bold; font-size: 12pt; margin-bottom: 14px; text-transform: uppercase; }
        .meta { margin-bottom: 12px; line-height: 1.5; }
        .meta td { padding: 2px 8px 2px 0; vertical-align: top; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 8.5pt; }
        table.data th, table.data td { border: 1px solid #333; padding: 4px 5px; vertical-align: top; }
        table.data th { background: #eee; text-align: center; font-weight: bold; }
        .footer-wrap { width: 100%; margin-top: 10px; font-size: 9pt; border-collapse: collapse; }
        .footer-wrap td { vertical-align: top; padding: 8px 6px; }
        .footer-wrap td.col-pegawai { text-align: center; width: 50%; }
        .footer-wrap td.col-menyetujui { text-align: center; width: 50%; }
        .footer-wrap td.col-pegawai-only { text-align: center; width: 100%; }
        .sig-title { font-weight: bold; margin-bottom: 4px; }
        .sig-name { font-weight: bold; text-decoration: underline; margin-top: 8px; }
        .approve-note { font-size: 8.5pt; margin: 8px auto 0; font-style: italic; text-align: center; line-height: 1.35; }
        .submit-note { font-size: 8.5pt; margin: 0 auto 6px; font-style: italic; text-align: center; line-height: 1.35; }
        .qr { margin-top: 10px; text-align: center; }
        .qr img { display: inline-block; margin: 0 auto; }
        .qr-caption { font-size: 7.5pt; color: #555; margin-top: 4px; text-align: center; }
        .place-date { text-align: right; margin-bottom: 8px; font-size: 9pt; }
    </style>
</head>
<body>
@php
    $jabatanMenyetujui = $jabatanMenyetujui ?? ($jabatanAtasan ?? '—');
    $subbagianMenyetujui = $subbagianMenyetujui ?? ($subbagianAtasan ?? '—');
    $menyetujuiTanpaSubbagian = $menyetujuiTanpaSubbagian ?? false;
    $qrPegawaiDataUri = $qrPegawaiDataUri ?? ($qrDataUri ?? null);
    $qrMenyetujuiDataUri = $qrMenyetujuiDataUri ?? ($qrDataUri ?? null);
    $diajukanAt = $diajukanAt ?? ($pengajuan->created_at ?? null);
    $hideMenyetujuiAtasan = $hideMenyetujuiAtasan ?? false;
@endphp
<div class="title">Laporan Kinerja Harian</div>

<table class="meta">
    <tr>
        <td><strong>NAMA</strong></td>
        <td>: {{ $pegawai?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td><strong>NIP</strong></td>
        <td>: {{ $pegawai?->nip ?? '—' }}</td>
    </tr>
    <tr>
        <td><strong>JABATAN</strong></td>
        <td>: {{ $jabatanPegawai }}@if(!$hideMenyetujuiAtasan && $subbagianPegawai && $subbagianPegawai !== '—') - {{ $subbagianPegawai }}@endif</td>
    </tr>
</table>

<table class="data">
    <thead>
    <tr>
        <th style="width:28px;">No</th>
        <th style="width:72px;">Tanggal</th>
        <th>Uraian tugas</th>
        <th style="width:120px;">Output</th>
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $i => $row)
        <tr>
            <td style="text-align:center;">{{ $i + 1 }}</td>
            <td>{{ \Illuminate\Support\Carbon::parse($row->tanggal)->locale('id')->translatedFormat('j/M/Y') }}</td>
            <td>{!! nl2br(e($row->kegiatan)) !!}</td>
            <td>{{ $row->output }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="place-date">{{ $kotaSurat }}, {{ $approvedAt->locale('id')->translatedFormat('d F Y') }}</div>

<table class="footer-wrap" cellspacing="0">
    <tr>
        @if($hideMenyetujuiAtasan)
            {{-- Kolom kiri kosong agar tanda tangan di sebelah kanan --}}
            <td class="col-pegawai" style="width:50%;">&nbsp;</td>
            <td class="col-menyetujui" style="width:50%;">
                <div class="sig-title"></div>
                <div>{{ strtoupper($jabatanPegawai) }}</div>
                @if(!empty($qrPegawaiDataUri))
                    <div class="qr"><img src="{{ $qrPegawaiDataUri }}" alt="QR Sekretaris" width="90" height="90"/></div>
                    <div class="qr-caption">Verifikasi dokumen LKH</div>
                @endif
                <div class="sig-name">{{ $pegawai?->name }}</div>
                <div>NIP. {{ $pegawai?->nip ?? '—' }}</div>
            </td>
        @else
            <td class="col-pegawai">
                <div class="sig-title"></div>
                <div>{{ strtoupper($jabatanPegawai) }}</div>
                @if($subbagianPegawai && $subbagianPegawai !== '—')
                    <div style="font-size:8.5pt;">{{ strtoupper($subbagianPegawai) }}</div>
                @endif
                @if(!empty($qrPegawaiDataUri))
                    <div class="qr"><img src="{{ $qrPegawaiDataUri }}" alt="QR Pengaju" width="90" height="90"/></div>
                    <div class="qr-caption">Verifikasi pengajuan LKH</div>
                @endif
                <div class="sig-name">{{ $pegawai?->name }}</div>
                <div>NIP. {{ $pegawai?->nip ?? '—' }}</div>
            </td>
            <td class="col-menyetujui">
                <div class="sig-title">MENYETUJUI ATASAN LANGSUNG</div>
                <div>{{ strtoupper($jabatanMenyetujui) }}</div>
                @if(! $menyetujuiTanpaSubbagian && $subbagianMenyetujui && $subbagianMenyetujui !== '—')
                    <div style="font-size:8.5pt;">{{ strtoupper($subbagianMenyetujui) }}</div>
                @endif
                @if(!empty($qrMenyetujuiDataUri))
                    <div class="qr"><img src="{{ $qrMenyetujuiDataUri }}" alt="QR Penyetuju" width="90" height="90"/></div>
                    <div class="qr-caption">Verifikasi persetujuan LKH</div>
                @endif
                <div class="sig-name">{{ $reviewer?->name ?? $atasan?->name ?? '—' }}</div>
                <div>NIP. {{ $reviewer?->nip ?? $atasan?->nip ?? '—' }}</div>
            </td>
        @endif
    </tr>
    @if(! $hideMenyetujuiAtasan)
        <tr>
            <td colspan="2">
                @if($diajukanAt)
                    <div class="submit-note">
                        <!-- Dokumen ini diajukan secara elektronik pada {{ $diajukanAt->locale('id')->translatedFormat('d F Y') }}
                        pukul {{ $diajukanAt->format('H:i') }} WIB. -->
                    </div>
                @endif
                <div class="approve-note">
                    Dokumen ini disetujui secara elektronik pada {{ $approvedAt->locale('id')->translatedFormat('d F Y') }}
                    pukul {{ $approvedAt->format('H:i') }} WIB.
                </div>
            </td>
        </tr>
    @endif
</table>
</body>
</html>
