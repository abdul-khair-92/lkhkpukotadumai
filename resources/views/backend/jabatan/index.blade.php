@extends('backend.main.index')
@push('title', $page->title ?? 'Jabatan')
@section('content')
    <div class="content-wrapper">
        <div class="container-full">
            @include('backend.main.menu.announcement')
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="me-auto">
                        <h3 class="page-title"><i class="{{ $page->icon ?? 'fa fa-briefcase' }}"></i> {{ $page->title ?? 'Jabatan' }}</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"> {{ $page->subtitle ?? 'Manajemen Data Jabatan' }}</li>
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
                                @if($user->create)
                                    <button type="button" class="btn-action pull-right btn btn-success btn-sm" data-title="Tambah" data-action="create" data-url="{{ $page->url ?? url('admin/jabatan') }}">
                                        <span class="fa fa-plus-circle"></span> Tambah
                                    </button>
                                @endif
                            </div>
                            <div class="box-body">
                                <table id="datatable" class="table table-bordered table-striped" style="width: 100%;">
                                    <thead>
                                    <tr>
                                        <th class="w-0">No</th>
                                        <th>Nama Jabatan</th>
                                        <th class="text-center w-0">Aksi</th>
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
    <script>
        $(document).ready(function () {
            $('#datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax: '{{ url("admin/jabatan/data") }}',
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'name', name: 'name'},
                    {data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center'}
                ]
            });
        });
    </script>
    <script src="{{ url('js/jquery-crud.js?id='.time()) }}"></script>
@endpush
