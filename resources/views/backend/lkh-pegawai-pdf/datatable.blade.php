<script>
$(document).ready(function () {
    $('#filter-bulan, #filter-tahun').select2({ width: '100%' });
    
    $('#btn-filter').click(function() {
        $('#datatable').DataTable().ajax.reload();
    });

	$('#datatable').DataTable({
        searchDelay: 2000,
		responsive: true,
		lengthChange: true,
        searching: true,
		processing: true,
		serverSide: true,
        lengthMenu: [[10, 25, 50, 100, 200, 500, -1], [10, 25, 50, 100, 200, 500, "All"]],
		ajax: {
			url: @json(route('lkh-pegawai-pdf.data')),
			data: function (d) {
				d.bulan = $('#filter-bulan').val();
				d.tahun = $('#filter-tahun').val();
			}
		},
		language: {
            // url: "{{ asset(config('master.app.web.assets').'/assets/vendor_components/datatable/indonesian.json') }}"
        },
		columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false, className: 'text-center' },
            { data: 'user_name', name: 'user.name', defaultContent: ''},
            { data: 'periode', orderable: false, searchable: false, defaultContent: ''},
			{ data: 'reviewed_at', defaultContent: ''},
			{ data: 'action', orderable: false, searchable: false, className: 'text-center'}
		]
	});
});
</script>
