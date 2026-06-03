<!DOCTYPE html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 7pt; color: #111; margin: 8px; }
        .title { text-align: center; font-weight: bold; font-size: 11pt; margin-bottom: 4px; text-transform: uppercase; }
        .subtitle { text-align: center; font-size: 8pt; margin-bottom: 10px; }
        table.rekap { width: 100%; border-collapse: collapse; }
        table.rekap th, table.rekap td { border: 1px solid #333; padding: 2px 1px; text-align: center; vertical-align: middle; }
        table.rekap th.col-nama, table.rekap td.col-nama { text-align: left; padding-left: 4px; font-size: 6.5pt; }
        table.rekap th.col-no { width: 18px; }
        table.rekap th.col-total { width: 24px; }
        table.rekap th.col-day { width: 14px; font-size: 6pt; }
        table.rekap thead th { background: #eee; font-weight: bold; }
        .holiday { background: #fde8e8; }
        .check {
            display: inline-block;
            width: 10px;
            height: 10px;
            line-height: 10px;
            border-radius: 50%;
            background: #28a745;
            color: #fff;
            font-size: 6pt;
            font-weight: bold;
        }
        .libur { color: #c0392b; font-weight: bold; font-size: 6pt; }
        .empty { color: #999; }
        .legend { font-size: 6pt; margin-bottom: 6px; }
        .place-date { text-align: right; font-size: 8pt; margin: 12px 0 8px; }
        table.footer-wrap { width: 100%; margin-top: 8px; font-size: 8pt; border: none; }
        table.footer-wrap td {
            vertical-align: top;
            width: 50%;
            padding: 4px 12px;
            text-align: center;
            border: none;
        }
        .sig-block { text-align: center; width: 100%; }
        .sig-label { font-size: 7pt; margin: 0 0 4px; }
        .sig-title { font-weight: bold; margin: 0 0 4px; text-transform: uppercase; line-height: 1.3; }
        .sig-sub {
            font-size: 7pt;
            margin: 0 0 20px;
            line-height: 1.35;
            text-transform: uppercase;
            height: 50px;
        }
        .sig-gap { height: 60px; }
        .sig-name { font-weight: bold; text-decoration: underline; margin: 0 0 4px; }
        .sig-nip { margin: 0; }
    </style>
</head>
<body>
@php
    $sign = $signatories ?? [];
    $kasubbag = $sign['kasubbag'] ?? null;
    $sekretaris = $sign['sekretaris'] ?? null;
@endphp

<div class="title">Rekap Laporan Kinerja Harian (LKH)</div>
<div class="subtitle">{{ $month_name }}</div>

<div class="legend">
    <strong>√</strong> = LKH terisi &nbsp;|&nbsp; <strong>-</strong> = belum diisi &nbsp;|&nbsp; <strong>L</strong> = libur / akhir pekan &nbsp;|&nbsp;
    <strong>Total</strong> = hari terisi (tidak termasuk libur)
</div>

<table class="rekap">
    <thead>
    <tr>
        <th rowspan="2" class="col-no">No</th>
        <th rowspan="2" class="col-nama">Nama Pegawai</th>
        <th colspan="{{ $days_in_month }}">{{ $month_name }}</th>
        <th rowspan="2" class="col-total">Total</th>
    </tr>
    <tr>
        @for($day = 1; $day <= $days_in_month; $day++)
            <th class="col-day {{ isset($holidays[$day]) ? 'holiday' : '' }}">{{ $day }}</th>
        @endfor
    </tr>
    </thead>
    <tbody>
    @foreach($rows as $row)
        <tr>
            <td>{{ $row['no'] }}</td>
            <td class="col-nama">{{ $row['name'] }}</td>
            @for($day = 1; $day <= $days_in_month; $day++)
                @php
                    $isHoliday = isset($holidays[$day]);
                    $hasLkh = $row['days'][$day] ?? false;
                @endphp
                <td class="{{ $isHoliday ? 'holiday' : '' }}">
                    @if($isHoliday && ! $hasLkh)
                        <span class="libur">L</span>
                    @elseif($hasLkh)
                        <span class="check">√</span>
                    @else
                        <span class="empty">-</span>
                    @endif
                </td>
            @endfor
            <td><strong>{{ $row['total'] }}</strong></td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="place-date">Dumai, {{ now()->locale('id')->translatedFormat('d F Y') }}</div>

<table class="footer-wrap" cellspacing="0">
    <tr>
        <td>
            <div class="sig-block">
                <p class="sig-label">Dibuat oleh,</p>
                <div class="sig-title">{{ strtoupper($sign['kasubbag_title'] ?? 'Kepala Subbagian') }}</div>
                @if(!empty($sign['kasubbag_subtitle']))
                    <p class="sig-sub">{{ strtoupper($sign['kasubbag_subtitle']) }}</p>
                @else
                    <div class="sig-gap"></div>
                @endif
                <div class="sig-name">{{ $kasubbag?->name ?? '................................' }}</div>
                <p class="sig-nip">NIP. {{ $kasubbag?->nip ?? '................................' }}</p>
            </div>
        </td>
        <td>
            <div class="sig-block">
                <p class="sig-label">Mengetahui,</p>
                <div class="sig-title">{{ strtoupper($sign['sekretaris_title'] ?? 'Sekretaris') }}</div>
                <div class="sig-gap"></div>
                <div class="sig-name">{{ $sekretaris?->name ?? '................................' }}</div>
                <p class="sig-nip">NIP. {{ $sekretaris?->nip ?? '................................' }}</p>
            </div>
        </td>
    </tr>
</table>
</body>
</html>
