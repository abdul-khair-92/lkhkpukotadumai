<script>
$(document).ready(function () {
    $('#filter-bulan, #filter-tahun').select2({ width: '100%' });

    var table = $('#datatable-monitoring').DataTable({
        searchDelay: 500,
        responsive: true,
        lengthChange: true,
        searching: true,
        processing: true,
        serverSide: true,
        ajax: {
            url: @json(route('lkh-monitoring.data')),
            data: function (d) {
                d.bulan = $('#filter-bulan').val();
                d.tahun = $('#filter-tahun').val();
            }
        },
        columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'nama_pegawai', name: 'nama_pegawai', orderable: false, searchable: true },
            { data: 'nip', name: 'nip' },
            { data: 'jumlah_lkh', name: 'jumlah_lkh', searchable: false, className: 'text-center' },
            { data: 'terakhir_input', name: 'terakhir_input', searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false, className: 'text-center' }
        ],
        dom: 'lfrtip'
    });

    $('#btn-filter-monitoring').on('click', function () {
        table.ajax.reload();
    });

    var base = @json(rtrim(route('lkh-monitoring.index'), '/'));
    function urlDetail(userId, bulan, tahun) {
        return base + '/detail/' + userId + '/' + bulan + '/' + tahun;
    }

    $(document).on('click', '.btn-lihat-monitoring', function () {
        var userId = $(this).data('id');
        var bulan = $(this).data('bulan');
        var tahun = $(this).data('tahun');
        var url = urlDetail(userId, bulan, tahun);
        $('#modal-monitoring-detail-content').html('<div class="p-20 text-center"><i class="fa fa-spinner fa-spin"></i> Memuat...</div>');
        $('#modal-monitoring-detail').modal('show');
        $.get(url).done(function (html) {
            $('#modal-monitoring-detail-content').html(html);
        }).fail(function () {
            $('#modal-monitoring-detail-content').html('<div class="modal-body"><div class="alert alert-danger">Gagal memuat detail monitoring.</div></div>');
        });
    });
});
</script>
