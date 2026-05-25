@extends('backend.main.index')
@push('title', $page->title ?? 'Lkh')
@push('css')
    <style>
        /* Force modal stack above all page widgets (select2, datatable buttons, etc.) */
        .modal-backdrop {
            z-index: 20000 !important;
        }

        .modal {
            z-index: 20010 !important;
        }

        /* Keep dropdown/select2 inside modal usable */
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
                                <h4 class="box-title">Content {!! $page->title ?? 'Page Name' !!}</h4>
                                <div class="pull-right d-flex align-items-center flex-wrap justify-content-end" style="gap: 10px;">
                                    <span id="lkh-status-pengajuan" class="text-end"></span>
                                    <a id="btn-lihat-pdf-laporan" href="#" class="btn btn-info btn-sm" style="display: none;" target="_blank" rel="noopener" title="Unduh laporan PDF yang disetujui">
                                        <span class="fa fa-file-pdf-o"></span> Laporan PDF
                                    </a>
                                    @if(!empty($is_sekretaris))
                                        <a id="btn-generate-pdf-sekretaris" href="#" class="btn btn-info btn-sm" style="display: none;" target="_blank" rel="noopener" title="Generate PDF laporan LKH periode terpilih">
                                            <span class="fa fa-file-pdf-o"></span> Generate PDF
                                        </a>
                                    @endif
                                    @if($user->create && empty($hide_pengajuan_laporan))
                                        <button type="button" id="btn-pengajuan-laporan" class="btn btn-primary btn-sm">
                                            <span class="fa fa-paper-plane"></span> Pengajuan Laporan
                                        </button>
                                    @endif
                                    @if($user->create)
                                        <button type="button" class="btn-action btn btn-success btn-sm" data-title="Tambah" data-action="create" data-url="{!! $page->url ?? '' !!}">
                                            <span class="fa fa-plus-circle"></span> Tambah Kegiatan
                                        </button>
                                    @endif
                                </div>
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
										<th class="w-0">No</th>
										<th>Tanggal</th>
										<th>Kegiatan</th>
										<th>Output</th>
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
@endsection
@push('js')
    <script src="{{ url($template.'/assets/vendor_components/select2/dist/js/select2.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/sweetalert/sweetalert.min.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/jquery-validation-1.17.0/lib/jquery.form.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/datatable/datatables.min.js') }}"></script>
    @include('backend.l-k-h.datatable')
    <script>
        $(function () {
            var $btnPengajuan = $('#btn-pengajuan-laporan');
            $btnPengajuan.on('click', function () {
                var bulan = $('#filter-bulan').val();
                var tahun = $('#filter-tahun').val();
                if (!bulan || !tahun) {
                    swal('Perhatian', 'Pilih bulan dan tahun tertentu (bukan Semua) sebelum mengajukan laporan.', 'warning');
                    return;
                }
                swal({
                    title: 'Ajukan laporan?',
                    text: 'Laporan LKH periode bulan terpilih akan dikirim ke atasan untuk ditinjau.',
                    type: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Ajukan',
                    cancelButtonText: 'Batal'
                }, function (confirmed) {
                    if (!confirmed) return;
                    $.ajax({
                        url: @json(route('l-k-h.pengajuan')),
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            _token: @json(csrf_token()),
                            bulan: bulan,
                            tahun: tahun
                        }
                    }).done(function (res) {
                        if (res.status) {
                            swal('Berhasil', res.message, 'success');
                            if ($.fn.DataTable && $('#datatable').length && $('#datatable').DataTable) {
                                $('#datatable').DataTable().ajax.reload(null, false);
                            }
                        } else {
                            swal('Gagal', res.message || 'Pengajuan gagal', 'error');
                        }
                    }).fail(function (xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan.';
                        swal('Gagal', msg, 'error');
                    });
                });
            });
        });
    </script>
    <script src="{{ url('js/jquery-crud.js') }}"></script>
@endpush
