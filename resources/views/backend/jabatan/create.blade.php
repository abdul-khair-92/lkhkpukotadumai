{{ html()->form('POST', url('admin/jabatan'))->id('form-create-jabatan')->class('form form-horizontal')->open() }}
<div class="panel shadow-sm">
    <div class="panel-body">
        <div class="form-group">
            {!! html()->label('Nama Jabatan','name')->class('control-label') !!}
            <span class="text-danger">*</span>
            {!! html()->text('name')->placeholder('Ketik nama jabatan ...')->class('form-control')->id('name')->required() !!}
        </div>
    </div>
</div>
<div class="row">
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
    $('.modal-title').html('<i class="fa fa-plus"></i> Tambah Data Jabatan');
</script>
