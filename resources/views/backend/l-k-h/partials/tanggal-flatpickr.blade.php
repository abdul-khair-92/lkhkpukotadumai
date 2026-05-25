<link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
<style>
    .flatpickr-calendar {
        z-index: 20030 !important;
    }
</style>
<script src="{{ asset('js/flatpickr.js') }}"></script>
<script src="{{ asset('js/id.js') }}"></script>
<script>
    window.initLkhTanggalFlatpickr = function (options) {
        options = options || {};
        var $input = $('#tanggal');
        if (!$input.length || typeof flatpickr === 'undefined') {
            return;
        }

        var allowDate = options.allowDate || null;
        var existing = $input.val() || null;
        var $modal = $input.closest('.modal');

        if ($input[0]._flatpickr) {
            $input[0]._flatpickr.destroy();
        }

        $.get(@json(route('l-k-h.picker-config')), function (cfg) {
            flatpickr($input[0], {
                dateFormat: 'Y-m-d',
                allowInput: false,
                clickOpens: true,
                maxDate: cfg.maxDate || 'today',
                locale: flatpickr.l10ns.id,
                defaultDate: existing || null,
                appendTo: $modal.length ? $modal[0] : document.body,
                disable: [
                    function (date) {
                        var y = date.getFullYear();
                        var m = String(date.getMonth() + 1).padStart(2, '0');
                        var d = String(date.getDate()).padStart(2, '0');
                        var key = y + '-' + m + '-' + d;

                        if (allowDate && key === allowDate) {
                            return false;
                        }

                        var day = date.getDay();
                        if (day === 0 || day === 6) {
                            return true;
                        }

                        return (cfg.holidays || []).indexOf(key) !== -1;
                    },
                ],
            });
        }).fail(function () {
            console.error('Gagal memuat konfigurasi tanggal LKH.');
        });
    };
</script>
