@php
    $k = trim((string) ($data->kegiatan ?? ''));
    $o = trim((string) ($data->output ?? ''));
    $statusKehadiran = 'hadir';
    if (strtoupper($k) === 'DL' && strtoupper($o) === 'DL') {
        $statusKehadiran = 'dl';
    } elseif (strtolower($k) === 'cuti' && strtolower($o) === 'cuti') {
        $statusKehadiran = 'cuti';
    } elseif (strtolower($k) === 'sakit' && strtolower($o) === 'sakit') {
        $statusKehadiran = 'sakit';
    }
    $tanggalValue = $data->tanggal ? \Illuminate\Support\Carbon::parse($data->tanggal)->format('Y-m-d') : null;
@endphp
{!! html()->modelForm($data,'PUT', route($page->url.'.update', $data->id))->id('form-create-'.$page->code)->acceptsFiles()->class('form form form-horizontal')->open() !!}
<div class="panel shadow-sm">
    <div class="panel-body">
        <div class='form-group'>
            {!! html()->label()->class('control-label')->for('tanggal')->text('Tanggal Kegiatan') !!}
            {!! html()->text('tanggal', $tanggalValue)->class('form-control lkh-tanggal-picker')->id('tanggal')->attribute('placeholder', 'Klik untuk pilih tanggal')->attribute('autocomplete', 'off') !!}
            <small class="text-muted d-block mt-5">Hari Sabtu/Minggu, hari libur, dan tanggal setelah hari ini tidak dapat dipilih.</small>
        </div>
        <div class="form-group">
            {!! html()->label()->class('control-label')->for('status_kehadiran')->text('Status Kehadiran') !!}
            {!! html()->select('status_kehadiran', [
                'hadir' => 'Hadir',
                'dl' => 'DL',
                'cuti' => 'Cuti',
                'sakit' => 'Sakit'
            ], $statusKehadiran)->class('form-control select2')->id('status_kehadiran') !!}
        </div>
        <div class='form-group'>
            {!! html()->label()->class('control-label')->for('kegiatan')->text('Kegiatan') !!}
            {!! html()->textarea('kegiatan', $data->kegiatan)->class('form-control')->id('kegiatan')->attribute('rows', 6)->attribute('style', 'min-height:120px;') !!}
        </div>
        <div class='form-group'>
            {!! html()->label()->class('control-label')->for('output')->text('Output') !!}
            {!! html()->text('output', $data->output)->placeholder('Type Output here')->class('form-control')->id('output') !!}
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

    #tanggal.lkh-tanggal-picker {
        background-color: #fff !important;
        cursor: pointer;
    }
</style>
@include('backend.l-k-h.partials.tanggal-flatpickr')
<script>
    $(function () {
        var $status = $('#status_kehadiran');
        var $modal = $status.closest('.modal');
        var editMode = true;

        if ($status.hasClass('select2-hidden-accessible')) {
            $status.select2('destroy');
        }
        $status.select2({
            width: '100%',
            dropdownParent: $modal.length ? $modal : $(document.body)
        });

        function toggleKegiatanOutput() {
            var status = $status.val();
            if (status === 'hadir') {
                $('#kegiatan').closest('.form-group').show();
                $('#output').closest('.form-group').show();
                $('#kegiatan').prop('disabled', false).prop('readonly', false);
                $('#output').prop('disabled', false).prop('readonly', false);
                if (!editMode) {
                    $('#kegiatan').val('');
                    $('#output').val('');
                }
            } else if (status === 'dl') {
                $('#kegiatan').closest('.form-group').show();
                $('#output').closest('.form-group').show();
                $('#kegiatan').prop('disabled', false).prop('readonly', false).val('DL');
                $('#output').prop('disabled', false).prop('readonly', false).val('DL');
            } else {
                $('#kegiatan').closest('.form-group').hide();
                $('#output').closest('.form-group').hide();
                var nilai = status.charAt(0).toUpperCase() + status.slice(1);
                $('#kegiatan').prop('disabled', false).prop('readonly', true).val(nilai);
                $('#output').prop('disabled', false).prop('readonly', true).val(nilai);
            }
        }

        $status.on('change', function () {
            editMode = false;
            toggleKegiatanOutput();
        });
        toggleKegiatanOutput();

        if (typeof window.initLkhTanggalFlatpickr === 'function') {
            window.initLkhTanggalFlatpickr({ allowDate: @json($tanggalValue) });
        }
    });

    $('.modal-title').html('<i class="fa fa-edit"></i> Edit Data {!! $page->title !!}');
    $('.submit-data').html('<i class="fa fa-save"></i> Simpan Data');
</script>
