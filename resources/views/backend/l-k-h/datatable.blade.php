<script>
function initLkhStatusPengajuanTooltip($container) {
    var $targets = $container.find('[data-toggle="tooltip"]');
    if (!$targets.length || typeof $.fn.tooltip !== 'function') {
        return;
    }
    $targets.each(function () {
        var $el = $(this);
        if ($el.data('bs.tooltip')) {
            $el.tooltip('dispose');
        }
    });
    $targets.tooltip({ container: 'body', trigger: 'hover focus' });
}

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
        lengthMenu: [[10, 25, 50, 100 ,200 , 500, -1], [10, 25, 50, 100 ,200 , 500, "All"]],
		ajax: {
			url: @json(route('l-k-h.data')),
			data: function (d) {
				d.bulan = $('#filter-bulan').val();
				d.tahun = $('#filter-tahun').val();
			},
			dataSrc: function (json) {
				var ui = json.pengajuan_ui;
				if (ui) {
					var $wrap = $('#lkh-status-pengajuan');
					if ($wrap.length) {
						$wrap.html(ui.status_html || '');
						initLkhStatusPengajuanTooltip($wrap);
					}
					var $btn = $('#btn-pengajuan-laporan');
					if ($btn.length) {
						if (ui.show_submit) {
							$btn.show();
						} else {
							$btn.hide();
						}
					}
					var $pdf = $('#btn-lihat-pdf-laporan');
					if ($pdf.length) {
						if (ui.show_pdf_button && ui.pdf_download_url) {
							$pdf.attr('href', ui.pdf_download_url).show();
						} else {
							$pdf.hide().attr('href', '#');
						}
					}
					var $genPdf = $('#btn-generate-pdf-sekretaris');
					if ($genPdf.length) {
						if (ui.show_generate_pdf_button && ui.generate_pdf_url) {
							$genPdf.attr('data-url', ui.generate_pdf_url).show();
						} else {
							$genPdf.hide().removeAttr('data-url');
						}
					}
				}
				return json.data;
			}
		},
		language: {
            {{-- Uncomment this line to use Indonesian language --}}
            {{--url: "{{ asset(config('master.app.web.assets').'/assets/vendor_components/datatable/indonesian.json') }}"--}}
        },
		columns: [
            { data: 'DT_RowIndex', name: 'DT_RowIndex',orderable: false, searchable: false, orderable: false, className: 'text-center' },
            { data: 'tanggal' , 'defaultContent':''},
            { data: 'kegiatan' , 'defaultContent':''},
			{ data: 'output' , 'defaultContent':''},
			{ data: 'action', orderable: false, searchable: false , className: 'text-center'}
		]
	});
});
</script>
