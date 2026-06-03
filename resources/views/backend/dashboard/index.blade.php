@extends('backend.main.index')
@push('title', 'Dashboard')
@push('css')
    <style>
        .lkh-dash-stat {
            border-radius: 8px;
            padding: 1.25rem;
            height: 100%;
        }
        .lkh-dash-stat .stat-value {
            display: block;
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.1;
        }
        .lkh-dash-stat .stat-label {
            display: block;
            margin-top: 0.35rem;
            color: #6c757d;
            font-size: 0.85rem;
            line-height: 1.35;
        }
        .lkh-today-banner {
            border-radius: 10px;
            padding: 1.5rem 1.75rem;
        }
        .lkh-cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 6px;
        }
        .lkh-cal-head {
            text-align: center;
            font-size: 0.75rem;
            font-weight: 600;
            color: #6c757d;
            padding: 4px 0;
        }
        .lkh-cal-cell {
            aspect-ratio: 1;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.85rem;
            font-weight: 600;
            position: relative;
            cursor: default;
        }
        .lkh-cal-cell.is-today {
            box-shadow: 0 0 0 2px #3b82f6;
        }
        .lkh-cal-cell.status-filled {
            background: #d1fae5;
            color: #065f46;
        }
        .lkh-cal-cell.status-empty {
            background: #fee2e2;
            color: #991b1b;
        }
        .lkh-cal-cell.status-holiday {
            background: #e5e7eb;
            color: #4b5563;
        }
        .lkh-cal-cell.status-future {
            background: #f8fafc;
            color: #94a3b8;
        }
        .lkh-cal-legend span {
            display: inline-flex;
            align-items: center;
            margin-right: 1rem;
            font-size: 0.8rem;
        }
        .lkh-cal-legend i {
            width: 14px;
            height: 14px;
            border-radius: 3px;
            display: inline-block;
            margin-right: 6px;
        }
        .lkh-recent-item {
            border-bottom: 1px solid #eee;
            padding: 0.75rem 0;
        }
        .lkh-recent-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .lkh-progress-thin {
            height: 8px;
            border-radius: 4px;
        }
        .lkh-welcome-compact .box-body {
            padding: 0.85rem 1.25rem !important;
        }
        .lkh-welcome-compact h1 {
            font-size: 1.35rem;
            margin-bottom: 0.15rem;
            line-height: 1.3;
        }
        .lkh-welcome-compact .welcome-sub {
            font-size: 0.875rem;
            margin-bottom: 0;
        }
        .lkh-welcome-compact .welcome-img {
            max-height: 72px;
            width: auto;
        }
        .lkh-summary-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }
        @media (max-width: 575px) {
            .lkh-summary-stats {
                grid-template-columns: 1fr;
            }
        }
        .lkh-summary-stat {
            text-align: center;
            padding: 0.75rem 0.5rem;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .lkh-summary-stat .stat-num {
            display: block;
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 0.35rem;
        }
        .lkh-summary-stat .stat-caption {
            display: block;
            font-size: 0.8rem;
            color: #6c757d;
            line-height: 1.35;
            word-break: break-word;
        }
    </style>
@endpush
@section('content')
    @php
        $d = $lkhDashboard;
        $summary = $d['summary'];
        $today = $d['today'];
        $calendar = $d['calendar'];
        $recent = $d['recent'];
        $isAtasan = $d['is_atasan'];
        $weekdays = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];
    @endphp
    <div class="content-wrapper">
        <div class="container-full">
            <section class="content">
                <div class="row align-items-end">
                    <div class="col-12">
                        <div class="box bg-primary-light overflow-hidden pull-up lkh-welcome-compact">
                            <div class="box-body">
                                <div class="row align-items-center g-10">
                                    <div class="col">
                                        <h1 class="text-dark mb-0">Halo, {{ $user->name }}!</h1>
                                        <p class="text-dark welcome-sub">
                                            @if($isAtasan)
                                                Ringkasan tim &amp; LKH — {{ $d['month_label'] }}
                                            @else
                                                Pengisian LKH — {{ $d['month_label'] }}
                                            @endif
                                        </p>
                                    </div>
                                    <div class="col-auto d-none d-md-block">
                                        <img class="welcome-img" src="{{ url($template.'/images/svg-icon/color-svg/lkh-dashboard.png') }}" alt="">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @include('backend.main.menu.announcement')
                </div>

                {{-- Status hari ini --}}
                <div class="row mt-20">
                    <div class="col-12">
                        @if($isAtasan)
                            <div class="row g-15">
                                <div class="col-lg-6">
                                    <div class="lkh-today-banner {{ ($today['is_holiday'] ?? false) ? 'bg-secondary-light' : (($today['is_filled'] ?? false) ? 'bg-success-light' : 'bg-warning-light') }}">
                                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-10">
                                            <div>
                                                <h4 class="mb-5">LKH Anda Hari Ini</h4>
                                                <p class="mb-0 text-muted">{{ $today['date_label'] }}</p>
                                                @if($today['is_holiday'] ?? false)
                                                    <p class="mb-0 mt-10"><span class="badge badge-secondary">{{ $today['holiday_label'] ?? 'Hari libur' }}</span></p>
                                                @elseif($today['is_filled'] ?? false)
                                                    <p class="mb-0 mt-10 text-success fw-600"><i class="fa fa-check-circle"></i> Sudah mengisi LKH hari ini</p>
                                                @else
                                                    <p class="mb-0 mt-10 text-warning fw-600"><i class="fa fa-exclamation-circle"></i> Belum mengisi LKH hari ini</p>
                                                @endif
                                            </div>
                                            @if(!($today['is_holiday'] ?? false) && !($today['is_filled'] ?? false))
                                                <a href="{{ $today['lkh_url'] }}" class="btn btn-success btn-sm">Isi LKH</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="lkh-today-banner bg-info-light">
                                        <h4 class="mb-5">Pegawai</h4>
                                        <p class="mb-0 text-muted">{{ $today['date_label'] }}</p>
                                        @if(($today['is_holiday'] ?? false))
                                            <p class="mb-0 mt-10 text-muted">Hari libur — tidak ada target pengisian tim.</p>
                                        @elseif(($today['jumlah_bawahan'] ?? 0) < 1)
                                            <p class="mb-0 mt-10 text-muted">Tidak ada pegawai dalam cakupan tim.</p>
                                        @elseif(($today['bawahan_belum_hari_ini'] ?? 0) > 0)
                                            <p class="mb-0 mt-10 text-danger fw-600">
                                                <i class="fa fa-users"></i>
                                                {{ $today['bawahan_belum_hari_ini'] }} dari {{ $today['jumlah_bawahan'] }} pegawai belum mengisi hari ini
                                            </p>
                                        @else
                                            <p class="mb-0 mt-10 text-success fw-600">
                                                <i class="fa fa-check-circle"></i> Semua pegawai tim sudah mengisi hari ini
                                            </p>
                                        @endif
                                        <div class="mt-15 d-flex flex-wrap gap-10">
                                            @if(($today['pending_pengajuan'] ?? 0) > 0)
                                                <a href="{{ $today['pengajuan_url'] }}" class="btn btn-warning btn-sm">
                                                    {{ $today['pending_pengajuan'] }} pengajuan menunggu
                                                </a>
                                            @endif
                                            @if(!empty($today['monitoring_url']))
                                                <a href="{{ $today['monitoring_url'] }}" class="btn btn-outline-primary btn-sm">Monitoring LKH</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="lkh-today-banner {{ ($today['is_holiday'] ?? false) ? 'bg-secondary-light' : (($today['is_filled'] ?? false) ? 'bg-success-light' : 'bg-warning-light') }}">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-10">
                                    <div>
                                        <h4 class="mb-5">Status Hari Ini</h4>
                                        <p class="mb-0 text-muted">{{ $today['date_label'] }}</p>
                                        @if($today['is_holiday'] ?? false)
                                            <p class="mb-0 mt-10"><span class="badge badge-secondary">{{ $today['holiday_label'] ?? 'Hari libur' }}</span> — tidak perlu mengisi LKH.</p>
                                        @elseif($today['is_filled'] ?? false)
                                            <p class="mb-0 mt-10 text-success fw-600 fs-18"><i class="fa fa-check-circle"></i> Sudah mengisi LKH hari ini</p>
                                        @else
                                            <p class="mb-0 mt-10 text-warning fw-600 fs-18"><i class="fa fa-exclamation-circle"></i> Belum mengisi LKH hari ini</p>
                                        @endif
                                    </div>
                                    @if(!($today['is_holiday'] ?? false) && !($today['is_filled'] ?? false))
                                        <a href="{{ $today['lkh_url'] }}" class="btn btn-success">Isi LKH Sekarang</a>
                                    @elseif($today['is_filled'] ?? false)
                                        <a href="{{ $today['lkh_url'] }}" class="btn btn-outline-primary btn-sm">Lihat LKH</a>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Ringkasan bulan ini --}}
                <div class="row mt-20">
                    <div class="col-12">
                        <div class="box">
                            <div class="box-header with-border">
                                <h4 class="box-title">Ringkasan LKH — {{ $d['month_label'] }}</h4>
                            </div>
                            <div class="box-body">
                                @if($isAtasan)
                                    <div class="row">
                                        <div class="col-md-3 col-sm-6 mb-20">
                                            <div class="lkh-dash-stat bg-primary-light">
                                                <div class="stat-value text-primary">{{ $summary['jumlah_bawahan'] }}</div>
                                                <div class="stat-label">Pegawai</div>
                                            </div>
                                        </div>
                                        <!-- <div class="col-md-3 col-sm-6 mb-20">
                                            <div class="lkh-dash-stat bg-success-light">
                                                <div class="stat-value text-success">{{ $summary['terisi_tim'] }}</div>
                                                <div class="stat-label">Total entri LKH tim (hari kerja)</div>
                                            </div>
                                        </div> -->
                                        <div class="col-md-3 col-sm-6 mb-20">
                                            <div class="lkh-dash-stat bg-warning-light">
                                                <div class="stat-value text-warning">{{ $summary['belum_isi_hari_ini'] }}</div>
                                                <div class="stat-label">Belum isi hari ini</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-sm-6 mb-20">
                                            <div class="lkh-dash-stat bg-danger-light">
                                                <div class="stat-value text-danger">{{ $summary['pending_pengajuan'] }}</div>
                                                <div class="stat-label">Pengajuan menunggu tinjauan</div>
                                            </div>
                                        </div>
                                    </div>
                                    @php $own = $summary['own']; @endphp
                                    <hr>
                                    <p class="text-muted mb-10"><strong>LKH pribadi Anda</strong> pada bulan ini</p>
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="lkh-summary-stats">
                                                <div class="lkh-summary-stat">
                                                    <span class="stat-num text-success">{{ $own['terisi'] }}</span>
                                                    <span class="stat-caption">Hari terisi</span>
                                                </div>
                                                <div class="lkh-summary-stat">
                                                    <span class="stat-num text-danger">{{ $own['belum'] }}</span>
                                                    <span class="stat-caption">Hari belum</span>
                                                </div>
                                                <div class="lkh-summary-stat">
                                                    <span class="stat-num text-primary">{{ $own['hari_kerja'] }}</span>
                                                    <span class="stat-caption">Hari kerja</span>
                                                </div>
                                            </div>
                                            <div class="progress lkh-progress-thin">
                                                <div class="progress-bar bg-success" style="width: {{ $own['persen'] }}%"></div>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-md-end mt-10 mt-md-0">
                                            <span class="fs-24 fw-600">{{ $own['persen'] }}%</span>
                                            @if(empty($own['hide_pengajuan']) && !empty($own['pengajuan']))
                                                <br>
                                                <span class="badge {{ $own['pengajuan']['badge_class'] }} mt-5">{{ $own['pengajuan']['label'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="row align-items-center">
                                        <div class="col-lg-8">
                                            <div class="lkh-summary-stats">
                                                <div class="lkh-summary-stat">
                                                    <span class="stat-num text-success">{{ $summary['terisi'] }}</span>
                                                    <span class="stat-caption">Hari terisi</span>
                                                </div>
                                                <div class="lkh-summary-stat">
                                                    <span class="stat-num text-danger">{{ $summary['belum'] }}</span>
                                                    <span class="stat-caption">Hari belum diisi</span>
                                                </div>
                                                <div class="lkh-summary-stat">
                                                    <span class="stat-num text-primary">{{ $summary['hari_kerja'] }}</span>
                                                    <span class="stat-caption">Hari kerja (s/d hari ini)</span>
                                                </div>
                                            </div>
                                            <div class="progress lkh-progress-thin mb-5">
                                                <div class="progress-bar bg-success" style="width: {{ $summary['persen'] }}%"></div>
                                            </div>
                                            <small class="text-muted d-block">Kelengkapan pengisian {{ $summary['persen'] }}%</small>
                                        </div>
                                        <div class="col-lg-4 text-lg-end mt-15 mt-lg-0">
                                            @if(empty($summary['hide_pengajuan']))
                                                <p class="mb-5 text-muted">Status pengajuan bulan ini</p>
                                                <span class="badge {{ $summary['pengajuan']['badge_class'] }} fs-14">{{ $summary['pengajuan']['label'] }}</span>
                                            @endif
                                            <div class="mt-15">
                                                <a href="{{ route('l-k-h.index') }}" class="btn btn-primary btn-sm">Kelola LKH</a>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- Kalender --}}
                    <div class="col-lg-7">
                        <div class="box">
                            <div class="box-header with-border">
                                <h4 class="box-title">Kalender Pengisian — {{ $d['month_label'] }}</h4>
                            </div>
                            <div class="box-body">
                                <div class="lkh-cal-legend mb-15">
                                    <span><i class="status-filled" style="background:#d1fae5;"></i> Sudah diisi</span>
                                    <span><i class="status-empty" style="background:#fee2e2;"></i> Belum diisi</span>
                                    <span><i class="status-holiday" style="background:#e5e7eb;"></i> Hari libur</span>
                                    <span><i class="status-future" style="background:#f8fafc;border:1px solid #e2e8f0;"></i> Mendatang</span>
                                </div>
                                <div class="lkh-cal-grid mb-6">
                                    @foreach($weekdays as $wd)
                                        <div class="lkh-cal-head">{{ $wd }}</div>
                                    @endforeach
                                    @for($i = 1; $i < $calendar['first_weekday']; $i++)
                                        <div></div>
                                    @endfor
                                    @foreach($calendar['cells'] as $cell)
                                        <div
                                            class="lkh-cal-cell status-{{ $cell['status'] }} {{ $cell['is_today'] ? 'is-today' : '' }}"
                                            @if($cell['label']) title="{{ $cell['label'] }}" @endif
                                        >{{ $cell['day'] }}</div>
                                    @endforeach
                                </div>
                                <p class="text-muted mb-0 fs-12">Kalender menampilkan pengisian LKH pribadi Anda. Akhir pekan dan libur nasional ditandai abu-abu.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Riwayat terbaru --}}
                    <div class="col-lg-5">
                        <div class="box">
                            <div class="box-header with-border">
                                <h4 class="box-title">
                                    @if($isAtasan)
                                        Pengajuan Terbaru dari Pegawai
                                    @else
                                        Riwayat LKH Terbaru
                                    @endif
                                </h4>
                                <div class="box-tools pull-right">
                                    @if($isAtasan)
                                        <a href="{{ route('lkh-pengajuan.index') }}" class="btn btn-sm btn-outline-primary">Semua</a>
                                    @else
                                        <a href="{{ route('l-k-h.index') }}" class="btn btn-sm btn-outline-primary">Semua</a>
                                    @endif
                                </div>
                            </div>
                            <div class="box-body">
                                @forelse($recent as $item)
                                    <div class="lkh-recent-item">
                                        @if($isAtasan)
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <strong>{{ $item['nama'] }}</strong>
                                                    <div class="text-muted fs-13">{{ $item['periode'] }}</div>
                                                </div>
                                                <span class="badge {{ $item['status_class'] }}">{{ $item['status_label'] }}</span>
                                            </div>
                                        @else
                                            <div class="d-flex justify-content-between">
                                                <strong>{{ $item['tanggal'] }}</strong>
                                                <span class="badge badge-success">Terisi</span>
                                            </div>
                                            <p class="mb-0 mt-5 text-muted fs-13">{!! nl2br(e($item['kegiatan'])) !!}</p>
                                            @if(!empty($item['output']))
                                                <small class="text-muted">Output: {!! nl2br(e($item['output'])) !!}</small>
                                            @endif
                                        @endif
                                    </div>
                                @empty
                                    <p class="text-muted text-center py-20 mb-0">
                                        @if($isAtasan)
                                            Belum ada pengajuan dari tim.
                                        @else
                                            Belum ada data LKH. Mulai isi kegiatan harian Anda.
                                        @endif
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ url($template.'/assets/vendor_components/jquery-validation-1.17.0/lib/jquery.form.js') }}"></script>
    <script src="{{ url('js/jquery-crud.js?id='.time()) }}"></script>
@endpush
