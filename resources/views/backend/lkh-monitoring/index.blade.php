@extends('backend.main.index')
@push('title', $page->title ?? 'Monitoring LKH')
@push('css')
    <style>
        .modal-backdrop { z-index: 20000 !important; }
        .modal { z-index: 20010 !important; }
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
                            <div class="box-header">
                                <h4 class="box-title">Daftar pegawai</h4>
                            </div>
                            <div class="box-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="filter-bulan" class="form-label">Bulan</label>
                                        <select id="filter-bulan" class="form-control select2">
                                            @foreach($list_bulan as $bulan => $label)
                                                <option value="{{ $bulan }}" @selected((string) $bulan === (string) ($filter_default_bulan ?? ''))>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="filter-tahun" class="form-label">Tahun</label>
                                        <select id="filter-tahun" class="form-control select2">
                                            @foreach($list_tahun as $tahun)
                                                <option value="{{ $tahun }}" @selected((string) $tahun === (string) ($filter_default_tahun ?? ''))>{{ $tahun }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2" style="padding-top: 28px;">
                                        <button id="btn-filter-monitoring" class="btn btn-primary btn-block" type="button">
                                            <i class="fa fa-filter"></i> Filter
                                        </button>
                                    </div>
                                </div>
                                <table id="datatable-monitoring" class="table table-bordered table-striped" style="width: 100%;">
                                    <thead>
                                    <tr>
                                        <th class="w-0">No</th>
                                        <th>Nama pegawai</th>
                                        <th>NIP</th>
                                        <th>Jumlah LKH</th>
                                        <th>Terakhir input</th>
                                        <th class="text-center w-0">Aksi</th>
                                    </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
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
    <script src="{{ url($template.'/assets/vendor_components/jquery-validation-1.17.0/lib/jquery.form.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/datatable/datatables.min.js') }}"></script>
    @include('backend.lkh-monitoring.datatable')
@endpush
