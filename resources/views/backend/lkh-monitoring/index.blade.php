@extends('backend.main.index')
@push('title', $page->title ?? 'Monitoring LKH')
@push('css')
    <style>
        .modal-backdrop { z-index: 20000 !important; }
        .modal { z-index: 20010 !important; }

        .monitoring-lkh-wrap { overflow-x: auto; }
        .table-monitoring-lkh { font-size: 12px; min-width: 900px; }
        .table-monitoring-lkh th,
        .table-monitoring-lkh td { text-align: center; vertical-align: middle; padding: 4px 3px; white-space: nowrap; }
        .table-monitoring-lkh th.col-nama,
        .table-monitoring-lkh td.col-nama { text-align: left; min-width: 180px; white-space: normal; }
        .table-monitoring-lkh th.col-no { width: 40px; }
        .table-monitoring-lkh th.col-total { width: 50px; }
        .table-monitoring-lkh th.col-day { width: 28px; min-width: 28px; }
        .table-monitoring-lkh th.holiday-col,
        .table-monitoring-lkh td.holiday-col { background-color: #fde8e8; }
        .monitoring-check {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #28a745;
            color: #fff;
            font-size: 10px;
            line-height: 1;
            cursor: pointer;
        }
        .monitoring-libur { font-size: 10px; font-weight: 600; color: #c0392b; }
        .monitoring-empty { color: #999; }
        #monitoring-loading { display: none; }

        /* Tooltip styling for kegiatan */
        .tooltip-inner {
            max-width: 300px;
            text-align: left;
            white-space: pre-wrap;
        }
    </style>
@endpush
@section('content')
    <div class="content-wrapper">
        <div class="container-full">
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="me-auto">
                        <h3 class="page-title"><i class="{!! $page->icon !!}"></i> {!! $page->title ?? 'Monitoring LKH' !!}</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">{!! $page->subtitle ?? 'Pantau pengisian LKH bawahan' !!}</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <section class="content">
                <div class="row">
                    <div class="col-12">
                        <div class="box">
                            <div class="box-header d-flex flex-wrap align-items-center justify-content-between" style="gap: 10px;">
                                <h4 class="box-title mb-0">Monitoring pengisian LKH</h4>
                                <div class="d-flex flex-wrap align-items-end" style="gap: 10px;">
                                    <div class="form-group mb-0">
                                        <label for="filter-bulan" class="form-label mb-1">Bulan</label>
                                        <select id="filter-bulan" class="form-control select2" style="min-width: 140px;">
                                            @foreach($list_bulan as $bulan => $label)
                                                <option value="{{ $bulan }}" @selected((string) $bulan === (string) ($filter_default_bulan ?? ''))>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group mb-0">
                                        <label for="filter-tahun" class="form-label mb-1">Tahun</label>
                                        <select id="filter-tahun" class="form-control select2" style="min-width: 100px;">
                                            @foreach($list_tahun as $tahun)
                                                <option value="{{ $tahun }}" @selected((string) $tahun === (string) ($filter_default_tahun ?? ''))>{{ $tahun }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button id="btn-filter-monitoring" type="button" class="btn btn-primary">
                                        <i class="fa fa-filter"></i> Tampilkan
                                    </button>
                                </div>
                            </div>
                            <div class="box-body">
                                <div id="monitoring-loading" class="text-center py-4">
                                    <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2 text-muted">Memuat data monitoring...</p>
                                </div>
                                <div id="monitoring-table-container" class="monitoring-lkh-wrap"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <div class="modal fade" id="modal-monitoring-detail" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content" id="modal-monitoring-detail-content"></div>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ url($template.'/assets/vendor_components/select2/dist/js/select2.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/sweetalert/sweetalert.min.js') }}"></script>
    <script>
        (function () {
            var dataUrl = @json(url(config('mvc.route_prefix').'/lkh-monitoring/data'));

            function loadMonitoring() {
                var bulan = parseInt($('#filter-bulan').val(), 10);
                var tahun = parseInt($('#filter-tahun').val(), 10);
                if (!bulan || !tahun) return;

                $('#monitoring-loading').show();
                $('#monitoring-table-container').empty();

                $.get(dataUrl + '/' + bulan + '/' + tahun)
                    .done(function (html) {
                        $('#monitoring-table-container').html(html);
                        // Initialize Bootstrap tooltips on the checkmark icons
                        $('[data-bs-toggle="tooltip"]').each(function () {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                                new bootstrap.Tooltip(this);
                            }
                        });
                    })
                    .fail(function () {
                        $('#monitoring-table-container').html('<div class="alert alert-danger">Gagal memuat data monitoring. Silakan coba lagi.</div>');
                    })
                    .always(function () {
                        $('#monitoring-loading').hide();
                    });
            }

            $(function () {
                $('#filter-bulan, #filter-tahun').select2({ width: '100%' });
                $('#btn-filter-monitoring').on('click', loadMonitoring);
                $('#filter-bulan, #filter-tahun').on('change', loadMonitoring);
                loadMonitoring();
            });
        })();
    </script>
@endpush
