{{ html()->form('POST', route($page->url.'.store'))->id('form-create-'.$page->code)->acceptsFiles()->class('form form form-horizontal')->open() }}
<div class="panel shadow-sm">
    <div class="panel-body">
        <div class='form-group'>
			{!! html()->label()->class('control-label')->for('tanggal')->text('Tanggal Kegiatan') !!}
			{!! html()->text('tanggal', null)->class('form-control lkh-tanggal-picker')->id('tanggal')->attribute('placeholder', 'Klik untuk pilih tanggal')->attribute('autocomplete', 'off') !!}
			<small class="text-muted d-block mt-5">Hari Sabtu/Minggu, hari libur, dan tanggal setelah hari ini tidak dapat dipilih.</small>
		</div>
		<div class='form-group'>
			{!! html()->label()->class('control-label')->for('kegiatan')->text('Kegiatan') !!}
			{!! html()->textarea('kegiatan',NULL)->class('form-control')->id('kegiatan')->attribute('rows', 6)->attribute('style', 'min-height:120px;') !!}
		</div>
		<div class='form-group'>
			{!! html()->label()->class('control-label')->for('output')->text('Output') !!}
			{!! html()->text('output',NULL)->placeholder('Type Output here')->class('form-control')->id('output') !!}
		</div>
    </div>
</div>
{!! html()->hidden('table-id','datatable')->id('table-id') !!}
{{--{!! html()->hidden('function','loadMenu,sidebarMenu')->id('function') !!}--}}
{{--{!! html()->hidden('redirect',url('/dashboard'))->id('redirect') !!}--}}
{!! html()->form()->close() !!}
<style>
    #form-create-{{ $page->code }} .select2-container {
        width: 100% !important;
    }

    .modal-lg {
        max-width: 1000px !important;
    }

    #tanggal.lkh-tanggal-picker {
        background-color: #fff !important;
        cursor: pointer;
    }
</style>
@include('backend.l-k-h.partials.tanggal-flatpickr')
<script>
    $(function () {
        if (typeof window.initLkhTanggalFlatpickr === 'function') {
            window.initLkhTanggalFlatpickr();
        }
    });

    $('.modal-title').html('<i class="fa fa-plus-circle"></i> Tambah Data {!! $page->title !!}');
    $('.submit-data').html('<i class="fa fa-save"></i> Simpan Data');
</script>
