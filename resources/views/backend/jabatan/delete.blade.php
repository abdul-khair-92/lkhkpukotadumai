{{ html()->form('DELETE', url('admin/jabatan/'.$data->id))->id('form-delete-jabatan')->class('form form-horizontal')->open() }}
<div class="panel shadow-sm">
    <div class="panel-body text-center">
        <h4 class="mb-3">Apakah Anda yakin ingin menghapus data jabatan ini?</h4>
        <p class="text-danger fw-bold fs-16">{{ $data->name }}</p>
    </div>
</div>
<div class="row mt-3">
    <div class="col-md-12">
        <span class="message"></span>
        <div class="progress" style="display: none;">
            <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                <div id="statustxt">0%</div>
            </div>
        </div>
    </div>
</div>
{!! html()->hidden('table-id','datatable')->id('table-id') !!}
{!! html()->form()->close() !!}
<script>
    $('.modal-title').html('<i class="fa fa-trash text-danger"></i> Hapus Data Jabatan');
</script>
