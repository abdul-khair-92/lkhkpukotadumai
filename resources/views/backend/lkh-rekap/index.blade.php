@extends('backend.main.index')
@push('title', $page->title ?? 'Rekap LKH')
@push('css')
    <style>
        .rekap-lkh-wrap { overflow-x: auto; }
        .table-rekap-lkh { font-size: 12px; min-width: 900px; }
        .table-rekap-lkh th,
        .table-rekap-lkh td { text-align: center; vertical-align: middle; padding: 4px 3px; white-space: nowrap; }
        .table-rekap-lkh th.col-nama,
        .table-rekap-lkh td.col-nama { text-align: left; min-width: 180px; white-space: normal; }
        .table-rekap-lkh th.col-no { width: 40px; }
        .table-rekap-lkh th.col-total { width: 50px; }
        .table-rekap-lkh th.col-day { width: 28px; min-width: 28px; }
        .table-rekap-lkh th.holiday-col,
        .table-rekap-lkh td.holiday-col { background-color: #fde8e8; }
        .rekap-check {
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
        }
        .rekap-libur { font-size: 10px; font-weight: 600; color: #c0392b; }
        .rekap-empty { color: #999; }
        #rekap-loading { display: none; }
    </style>
@endpush
@section('content')
    <div class="content-wrapper">
        <div class="container-full">
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="me-auto">
                        <h3 class="page-title"><i class="{!! $page->icon !!}"></i> {!! $page->title ?? 'Rekap LKH' !!}</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">{!! $page->subtitle ?? 'Rekapitulasi pengisian LKH pegawai per bulan' !!}</li>
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
                                <h4 class="box-title mb-0">Rekap pengisian LKH</h4>
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
                                    <button id="btn-filter-rekap" type="button" class="btn btn-primary">
                                        <i class="fa fa-filter"></i> Tampilkan
                                    </button>
                                    <a id="btn-export-pdf" href="#" class="btn btn-danger" target="_blank" rel="noopener">
                                        <i class="fa fa-file-pdf-o"></i> Cetak PDF
                                    </a>
                                </div>
                            </div>
                            <div class="box-body">
                                <div id="rekap-loading" class="text-center py-4">
                                    <i class="fa fa-spinner fa-spin fa-2x text-primary"></i>
                                    <p class="mt-2 text-muted">Memuat data rekap...</p>
                                </div>
                                <div id="rekap-table-container" class="rekap-lkh-wrap"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ url($template.'/assets/vendor_components/select2/dist/js/select2.js') }}"></script>
    <script>
        (function () {
            var dataUrl = @json(url(config('mvc.route_prefix').'/lkh-rekap/data'));
            var pdfUrl = @json(url(config('mvc.route_prefix').'/lkh-rekap/pdf'));

            function loadRekap() {
                var bulan = parseInt($('#filter-bulan').val(), 10);
                var tahun = parseInt($('#filter-tahun').val(), 10);
                if (!bulan || !tahun) return;

                $('#btn-export-pdf').attr('href', pdfUrl + '/' + bulan + '/' + tahun);
                $('#rekap-loading').show();
                $('#rekap-table-container').empty();

                $.get(dataUrl + '/' + bulan + '/' + tahun)
                    .done(function (html) {
                        $('#rekap-table-container').html(html);
                    })
                    .fail(function () {
                        $('#rekap-table-container').html('<div class="alert alert-danger">Gagal memuat rekap. Silakan coba lagi.</div>');
                    })
                    .always(function () {
                        $('#rekap-loading').hide();
                    });
            }

            $(function () {
                $('#filter-bulan, #filter-tahun').select2({ width: '100%' });
                $('#btn-filter-rekap').on('click', loadRekap);
                $('#filter-bulan, #filter-tahun').on('change', loadRekap);
                loadRekap();
            });
        })();
    </script>
@endpush
