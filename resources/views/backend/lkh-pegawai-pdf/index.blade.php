@extends('backend.main.index')
@push('title', $page->title ?? 'Laporan PDF Pegawai')
@push('css')
    <style>
        .modal-backdrop {
            z-index: 20000 !important;
        }

        .modal {
            z-index: 20010 !important;
        }

        .modal .select2-container,
        .modal .select2-dropdown {
            z-index: 20020 !important;
        }
    </style>
@endpush
@section('content')
    <div class="content-wrapper">
        <div class="container-full">
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="me-auto">
                        <h3 class="page-title"><i class="{!! $page->icon !!}"></i> {!! $page->title ?? 'Page Name' !!} </h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"> {!! $page->subtitle ?? 'Welcome to '.$page->title.' page' !!}</li>
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
                                <h4 class="box-title">{!! $page->title ?? 'Page Name' !!}</h4>
                            </div>

                            <div class="box-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <label for="filter-bulan" class="form-label">Bulan</label>
                                        <select id="filter-bulan" class="form-control select2">
                                            <option value="">Semua Bulan</option>
                                            @foreach($list_bulan as $bulan => $label)
                                                <option value="{{ $bulan }}" @selected((string) $bulan === (string) ($filter_default_bulan ?? ''))>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="filter-tahun" class="form-label">Tahun</label>
                                        <select id="filter-tahun" class="form-control select2">
                                            <option value="">Semua Tahun</option>
                                            @foreach($list_tahun as $tahun)
                                                <option value="{{ $tahun }}" @selected((string) $tahun === (string) ($filter_default_tahun ?? ''))>{{ $tahun }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2" style="padding-top: 28px;">
                                        <button id="btn-filter" class="btn btn-primary btn-block" type="button">
                                            <i class="fa fa-filter"></i> Filter
                                        </button>
                                    </div>
                                </div>
                        
                                <table id="datatable" class="table table-bordered table-striped" style="width: 100%;">
									<thead>
									<tr>
										<th class="w-0 text-center">No</th>
										<th>Nama Pegawai</th>
										<th>Periode</th>
										<th>Tanggal Disetujui</th>
										<th class="text-center w-0">Action</th>
									</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modal Preview PDF -->
    <div class="modal fade" id="modal-preview-pdf" tabindex="-1" role="dialog" aria-labelledby="modal-preview-pdf-label" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal-preview-pdf-label">Preview Laporan PDF Pegawai</h5>
                    <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" style="height: 75vh;">
                    <iframe id="iframe-preview-pdf" src="" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ url($template.'/assets/vendor_components/select2/dist/js/select2.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/datatable/datatables.min.js') }}"></script>
    @include('backend.lkh-pegawai-pdf.datatable')
    <script>
        $(function () {
            $(document).on('click', '.btn-lihat-pdf-laporan', function(e) {
                var url = $(this).attr('data-url');
                if (url) {
                    e.preventDefault();
                    $('#iframe-preview-pdf').attr('src', url);
                    var modal = new bootstrap.Modal(document.getElementById('modal-preview-pdf'));
                    modal.show();
                }
            });
            
            // clear iframe when modal is hidden to stop audio/video/loading
            $('#modal-preview-pdf').on('hidden.bs.modal', function () {
                $('#iframe-preview-pdf').attr('src', '');
            });
        });
    </script>
@endpush
