@php
    $tanggalValue = $data->tanggal ? \Illuminate\Support\Carbon::parse($data->tanggal)->format('Y-m-d') : null;
@endphp
{!! html()->modelForm($data,'PUT', route($page->url.'.update', $data->id))->id('form-create-'.$page->code)->acceptsFiles()->class('form form form-horizontal')->open() !!}
<div class="panel shadow-sm">
    <div class="panel-body">
        {!! html()->hidden('tanggal', $tanggalValue)->id('tanggal') !!}
        <div class='form-group'>
            {!! html()->label()->class('control-label')->for('kegiatan')->text('Kegiatan') !!}
            {!! html()->textarea('kegiatan', $data->kegiatan)->class('form-control')->id('kegiatan')->attribute('rows', 6)->attribute('style', 'min-height:120px;') !!}
        </div>
        <div class='form-group'>
            {!! html()->label()->class('control-label')->for('output')->text('Output') !!}
            {!! html()->textarea('output', $data->output)->placeholder('Ketik Output di sini')->class('form-control')->id('output')->attribute('rows', 3)->attribute('style', 'min-height:80px;') !!}
        </div>
    </div>
</div>
{!! html()->hidden('table-id','datatable')->id('table-id') !!}
{!! html()->closeModelForm() !!}
<style>
    #form-create-{{ $page->code }} .select2-container {
        width: 100% !important;
    }

    .modal-lg {
        max-width: 1000px !important;
    }
</style>
<script>

    $('.modal-title').html('<i class="fa fa-edit"></i> Edit Data {!! $page->title !!}');
    $('.submit-data').html('<i class="fa fa-save"></i> Simpan Data');
</script>
