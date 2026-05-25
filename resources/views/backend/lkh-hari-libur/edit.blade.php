{{ html()->form('PUT', route($page->url.'.update', $data->id))->id('form-create-'.$page->code)->class('form form-horizontal')->open() }}
<div class="panel shadow-sm">
    <div class="panel-body">
        <div class="form-group">
            {!! html()->label('tanggal', 'Tanggal')->class('control-label') !!}
            <span class="text-danger">*</span>
            {!! html()->date('tanggal', $data->tanggal?->format('Y-m-d'))->class('form-control')->id('tanggal')->required() !!}
        </div>
        <div class="form-group">
            {!! html()->label('keterangan', 'Keterangan')->class('control-label') !!}
            {!! html()->text('keterangan', $data->keterangan)->placeholder('Contoh: Hari Raya Idul Fitri')->class('form-control')->id('keterangan') !!}
        </div>
    </div>
</div>
{!! html()->hidden('table-id','datatable')->id('table-id') !!}
{!! html()->form()->close() !!}
<script>
    $('.modal-title').html('<i class="fa fa-edit"></i> Edit Hari Libur');
    $('.submit-data').html('<i class="fa fa-save"></i> Simpan');
</script>
