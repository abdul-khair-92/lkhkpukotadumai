@extends('backend.main.index')
@push('title', $page->title ?? 'Hari Libur')
@section('content')
    @php $tag = 'div'; @endphp
    <div class="content-wrapper">
        <div class="container-full">
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <{{ $tag }} class="me-auto">
                        <h3 class="page-title"><i class="{{ $page->icon }}"></i> {{ $page->title ?? 'Hari Libur' }}</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item">{{ $page->subtitle ?? 'Kelola hari libur nasional untuk rekap LKH' }}</li>
                                </ol>
                            </nav>
                        </div>
                    </{{ $tag }}>
                </div>
            </div>
            <section class="content">
                <{{ $tag }} class="row">
                    <{{ $tag }} class="col-12">
                        <div class="box">
                            <div class="box-header">
                                <h4 class="box-title">Daftar hari libur</h4>
                                <p class="text-muted mb-0 small">Akhir pekan (Sabtu–Minggu) otomatis dianggap libur pada rekap. Input di sini untuk libur nasional / cuti bersama.</p>
                                @if($user->create)
                                    <button type="button" class="btn-action pull-right btn btn-success btn-sm" data-title="Tambah" data-action="create" data-url="{{ $page->url ?? '' }}">
                                        <span class="fa fa-plus-circle"></span> Tambah
                                    </button>
                                @endif
                            </div>
                            <div class="box-body">
                                <table id="datatable" class="table table-bordered table-striped" style="width: 100%;">
                                    <thead>
                                    <tr>
                                        <th class="w-0">No</th>
                                        <th>Tanggal</th>
                                        <th>Keterangan</th>
                                        <th class="text-center w-0">Aksi</th>
                                    </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </{{ $tag }}>
                </{{ $tag }}>
            </section>
        </div>
    </div>
@endsection
@push('js')
    <script src="{{ url($template.'/assets/vendor_components/select2/dist/js/select2.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/sweetalert/sweetalert.min.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/jquery-validation-1.17.0/lib/jquery.form.js') }}"></script>
    <script src="{{ url($template.'/assets/vendor_components/datatable/datatables.min.js') }}"></script>
    @include('backend.lkh-hari-libur.datatable')
    <script src="{{ url('js/jquery-crud.js?id='.time()) }}"></script>
@endpush
