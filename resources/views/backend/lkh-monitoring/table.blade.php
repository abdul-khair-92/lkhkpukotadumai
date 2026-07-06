@if(empty($rows))
    <div class="alert alert-info mb-0">Tidak ada data pegawai untuk ditampilkan.</div>
@else
    <p class="text-muted mb-2">
        <strong>Periode:</strong> {{ $month_name }}
        <span class="ms-3"><span class="monitoring-check"><i class="fa fa-check"></i></span> = LKH terisi (hover untuk lihat kegiatan)</span>
        <span class="ms-2"><span class="monitoring-empty">-</span> = belum diisi</span>
        <span class="ms-2"><span class="monitoring-libur">L</span> = libur / akhir pekan</span>
        <span class="ms-2"><strong>Total</strong> = hari terisi (tidak termasuk libur)</span>
    </p>
    <table class="table table-bordered table-monitoring-lkh mb-0">
        <thead>
        <tr>
            <th rowspan="2" class="col-no">No</th>
            <th rowspan="2" class="col-nama">Nama Pegawai</th>
            <th colspan="{{ $days_in_month }}" class="text-center">{{ $month_name }}</th>
            <th rowspan="2" class="col-total">Total</th>
        </tr>
        <tr>
            @for($day = 1; $day <= $days_in_month; $day++)
                <th class="col-day {{ isset($holidays[$day]) ? 'holiday-col' : '' }}" title="{{ $holidays[$day] ?? '' }}">{{ $day }}</th>
            @endfor
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $row)
            <tr>
                <td>{{ $row['no'] }}</td>
                <td class="col-nama">{{ $row['name'] }}</td>
                @for($day = 1; $day <= $days_in_month; $day++)
                    @php
                        $isHoliday = isset($holidays[$day]);
                        $dayData = $row['days'][$day] ?? ['filled' => false, 'kegiatan' => '', 'output' => ''];
                        $hasLkh = $dayData['filled'];
                        $kegiatan = $dayData['kegiatan'];
                        $output = $dayData['output'];

                        // Build tooltip text
                        $tooltipParts = [];
                        if ($kegiatan) {
                            $tooltipParts[] = '<b>Kegiatan:</b> ' . e($kegiatan);
                        }
                        if ($output) {
                            $tooltipParts[] = '<b>Output:</b> ' . e($output);
                        }
                        $tooltipText = $tooltipParts ? implode('<br>', $tooltipParts) : 'LKH terisi';
                    @endphp
                    <td class="{{ $isHoliday ? 'holiday-col' : '' }}">
                        @if($isHoliday && ! $hasLkh)
                            <span class="monitoring-libur" title="{{ $holidays[$day] }}">L</span>
                        @elseif($hasLkh)
                            <span class="monitoring-check" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-html="true" data-bs-title="{!! $tooltipText !!}"><i class="fa fa-check"></i></span>
                        @else
                            <span class="monitoring-empty">-</span>
                        @endif
                    </td>
                @endfor
                <td><strong>{{ $row['total'] }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
