@extends('backend.main.index')
@push('title', $page->title ?? 'Pengajuan LKH')
@push('css')
    <style>
        .modal-backdrop { z-index: 20000 !important; }
        .modal { z-index: 20010 !important; }
        .modal .select2-container,
        .modal .select2-dropdown { z-index: 20020 !important; }
    </style>
@endpush
@section('content')
    <div class="content-wrapper">
        <div class="container-full">
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="me-auto">
                        <h3 class="page-title"><i class="{!! $page->icon !!}"></i> {!! $page->title ?? 'Pengajuan LKH' !!}</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">{!! $page->subtitle ?? 'Daftar pengajuan laporan kinerja harian pegawai' !!}</li>
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
                                <h4 class="box-title">Daftar pengajuan</h4>
                            </div>
                            <div class="box-body">
                                <table id="datatable" class="table table-bordered table-striped" style="width: 100%;">
                                    <thead>
                                    <tr>
                                        <th class="w-0">No</th>
                                        <th>Nama pegawai</th>
                                        <th>Bulan</th>
                                        <th>Tahun</th>
                                        <th>Status</th>
                                        <th>Tanggal pengajuan</th>
                                        <th>Tindakan atasan</th>
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

    <div class="modal fade" id="modal-laporan-lkh" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content" id="modal-laporan-lkh-content"></div>
        </div>
    </div>

    <div class="modal fade" id="modal-revisi-lkh" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Revisi laporan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="revisi-pengajuan-id" value="">
                    <div class="form-group">
                        <label for="revisi-catatan">Catatan untuk pegawai</label>
                        <textarea id="revisi-catatan" class="form-control" rows="5" placeholder="Jelaskan bagian yang perlu diperbaiki"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-warning" id="btn-kirim-revisi">Kirim revisi</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ url($template.'/assets/vendor_components/select2/dist/js/select2.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/sweetalert/sweetalert.min.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/jquery-validation-1.17.0/lib/jquery.form.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/datatable/datatables.min.js') }}"></script>
    @include('backend.lkh-pengajuan.datatable')
@endpush
