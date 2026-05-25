<script>
$(document).ready(function () {
    $('#datatable').DataTable({
        searchDelay: 500,
        responsive: true,
        lengthChange: true,
        processing: true,
        serverSide: true,
        ajax: @json(route('lkh-hari-libur.data')),
        columns: [
            { data: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'tanggal', defaultContent: '' },
            { data: 'keterangan', defaultContent: '' },
            { data: 'action', orderable: false, searchable: false, className: 'text-center' }
        ]
    });
});
</script>
