@if(empty($rows))
    <div class="alert alert-info mb-0">Tidak ada data pegawai untuk ditampilkan.</div>
@else
    <p class="text-muted mb-2">
        <strong>Periode:</strong> {{ $month_name }}
        <span class="ms-3"><span class="rekap-check"><i class="fa fa-check"></i></span> = LKH terisi</span>
        <span class="ms-2"><span class="rekap-empty">-</span> = belum diisi</span>
        <span class="ms-2"><span class="rekap-libur">L</span> = libur / akhir pekan</span>
        <span class="ms-2"><strong>Total</strong> = hari terisi (tidak termasuk libur)</span>
    </p>
    <table class="table table-bordered table-rekap-lkh mb-0">
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
                        $hasLkh = $row['days'][$day] ?? false;
                    @endphp
                    <td class="{{ $isHoliday ? 'holiday-col' : '' }}">
                        @if($isHoliday && ! $hasLkh)
                            <span class="rekap-libur" title="{{ $holidays[$day] }}">L</span>
                        @elseif($hasLkh)
                            <span class="rekap-check" title="{{ $isHoliday ? 'LKH terisi (hari libur, tidak dihitung total)' : 'LKH terisi' }}"><i class="fa fa-check"></i></span>
                        @else
                            <span class="rekap-empty">-</span>
                        @endif
                    </td>
                @endfor
                <td><strong>{{ $row['total'] }}</strong></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif
