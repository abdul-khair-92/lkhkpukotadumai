<script>
$(document).ready(function () {
    var table = $('#datatable').DataTable({
        searchDelay: 500,
        responsive: true,
        lengthChange: true,
        searching: true,
        processing: true,
        serverSide: true,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: @json(route('lkh-pengajuan.data')),
            data: function (d) { }
        },
        language: {},
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'nama_pegawai', name: 'nama_pegawai', orderable: false, searchable: true },
            { data: 'bulan', name: 'bulan' },
            { data: 'tahun', name: 'tahun' },
            { data: 'status_label', name: 'status', orderable: false, searchable: false },
            { data: 'created_at', name: 'created_at' },
            { data: 'reviewed_info', name: 'reviewed_at', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        dom: 'lfrtip',
        order: [[5, 'desc']]
    });

    var base = @json(rtrim(route('lkh-pengajuan.index'), '/'));
    var laporanUrlTpl = base + '/laporan/__ID__';
    var csrf = @json(csrf_token());

    function urlLaporan(id) { return base + '/laporan/' + id; }
    function urlApprove(id) { return base + '/' + id + '/approve'; }
    function urlRevisi(id) { return base + '/' + id + '/revisi'; }
    function urlBatalkan(id) { return base + '/' + id + '/batalkan'; }

    $(document).on('click', '.btn-lihat-laporan', function () {
        var id = $(this).data('id');
        var url = urlLaporan(id);
        $('#modal-laporan-lkh-content').html('<div class="p-20 text-center"><i class="fa fa-spinner fa-spin"></i> Memuat...</div>');
        $('#modal-laporan-lkh').modal('show');
        $.get(url).done(function (html) {
            $('#modal-laporan-lkh-content').html(html);
        }).fail(function () {
            $('#modal-laporan-lkh-content').html('<div class="modal-body"><div class="alert alert-danger">Gagal memuat laporan.</div></div>');
        });
    });

    $(document).on('click', '.btn-approve-pengajuan', function () {
        var id = $(this).data('id');
        var url = urlApprove(id);
        swal({
            title: 'Setujui laporan?',
            text: 'Pegawai akan mendapat status disetujui untuk periode ini.',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, setujui',
            cancelButtonText: 'Batal'
        }, function (ok) {
            if (!ok) return;
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: { _token: csrf }
            }).done(function (res) {
                if (res.status) {
                    swal('Berhasil', res.message, 'success');
                    table.ajax.reload(null, false);
                } else {
                    swal('Gagal', res.message || 'Gagal menyetujui', 'error');
                }
            }).fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan.';
                swal('Gagal', msg, 'error');
            });
        });
    });

    $(document).on('click', '.btn-revisi-pengajuan', function () {
        $('#revisi-pengajuan-id').val($(this).data('id'));
        $('#revisi-catatan').val('');
        $('#modal-revisi-lkh').modal('show');
    });

    $('#btn-kirim-revisi').on('click', function () {
        var id = $('#revisi-pengajuan-id').val();
        var catatan = $('#revisi-catatan').val();
        if (!catatan || !catatan.trim()) {
            swal('Perhatian', 'Isi catatan revisi untuk pegawai.', 'warning');
            return;
        }
        var url = urlRevisi(id);
        $.ajax({
            url: url,
            type: 'POST',
            dataType: 'json',
            data: { _token: csrf, catatan_atasan: catatan }
        }).done(function (res) {
            if (res.status) {
                $('#modal-revisi-lkh').modal('hide');
                swal('Berhasil', res.message, 'success');
                table.ajax.reload(null, false);
            } else {
                swal('Gagal', res.message || 'Gagal mengirim revisi', 'error');
            }
        }).fail(function (xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan.';
            if (xhr.responseJSON && xhr.responseJSON.errors && xhr.responseJSON.errors.catatan_atasan) {
                msg = xhr.responseJSON.errors.catatan_atasan[0];
            }
            swal('Gagal', msg, 'error');
        });
    });

    $(document).on('click', '.btn-batalkan-pengajuan', function () {
        var id = $(this).data('id');
        var url = urlBatalkan(id);
        swal({
            title: 'Batalkan persetujuan?',
            text: 'Status pengajuan akan menjadi Dibatalkan. Pegawai dapat mengubah LKH dan mengajukan ulang.',
            type: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, batalkan',
            cancelButtonText: 'Tidak'
        }, function (ok) {
            if (!ok) return;
            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: { _token: csrf }
            }).done(function (res) {
                if (res.status) {
                    swal('Berhasil', res.message, 'success');
                    table.ajax.reload(null, false);
                } else {
                    swal('Gagal', res.message || 'Gagal membatalkan', 'error');
                }
            }).fail(function (xhr) {
                var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan.';
                swal('Gagal', msg, 'error');
            });
        });
    });
});
</script>
