{!! html()->form('DELETE', route($page->url.'.destroy', $data->id))->id('form-create-'.$page->code)->class('form form-horizontal')->open() !!}
<div class="row">
    <div class="col-md-12">
        <label class="control-label h6">Hapus hari libur ini?</label>
        <div class="panel">
            <div class="panel-body panel-dark bg-dark">
                <p><strong>Tanggal:</strong> {{ $data->tanggal?->locale('id')->translatedFormat('d F Y') }}</p>
                <p><strong>Keterangan:</strong> {{ $data->keterangan ?: '—' }}</p>
            </div>
        </div>
    </div>
</div>
{!! html()->hidden('table-id','datatable')->id('table-id') !!}
{!! html()->form()->close() !!}
<script>
    $('.modal-title').html('<i class="fa fa-trash"></i> Hapus Hari Libur');
    $('.submit-data').html('<i class="fa fa-trash"></i> Hapus');
</script>
