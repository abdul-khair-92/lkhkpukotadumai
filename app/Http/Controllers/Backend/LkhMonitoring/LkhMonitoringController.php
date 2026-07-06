<?php

namespace App\Http\Controllers\Backend\LkhMonitoring;

use App\Http\Controllers\Controller;
use App\Models\LKH;
use App\Models\User;
use App\Services\LkhHolidayService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LkhMonitoringController extends Controller
{
    public function index()
    {
        $list_bulan = collect(range(1, 12))
            ->mapWithKeys(function ($bulan) {
                $monthValue = str_pad((string) $bulan, 2, '0', STR_PAD_LEFT);

                return [
                    $monthValue => Carbon::createFromFormat('m', $monthValue)->locale('id')->translatedFormat('F'),
                ];
            });

        $tahunBerjalan = (int) now()->format('Y');
        $list_tahun = collect(range($tahunBerjalan - 2, $tahunBerjalan + 1))->sortDesc()->values();

        return view($this->view.'.index', [
            'list_bulan' => $list_bulan,
            'list_tahun' => $list_tahun,
            'filter_default_bulan' => now()->format('m'),
            'filter_default_tahun' => (string) $tahunBerjalan,
        ]);
    }

    public function data(Request $request, LkhHolidayService $holidayService, int $bulan, int $tahun)
    {
        $auth = $request->user();
        $bulan = max(1, min(12, $bulan));
        $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;
        $monthName = Carbon::create($tahun, $bulan, 1)->locale('id')->translatedFormat('F Y');
        $holidays = $holidayService->holidaysInMonth($bulan, $tahun);

        $employees = $this->scopedPegawaiQuery($auth)
            ->get()
            ->sortBy(fn (User $u) => $u->name)
            ->values();

        $userIds = $employees->pluck('id');

        // Get LKH entries with kegiatan for tooltip
        $filledByUser = LKH::query()
            ->whereIn('user_id', $userIds)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get(['user_id', 'tanggal', 'kegiatan', 'output'])
            ->groupBy('user_id')
            ->map(function (Collection $rows) {
                return $rows->mapWithKeys(function ($row) {
                    $day = (int) Carbon::parse($row->tanggal)->day;

                    return [$day => [
                        'kegiatan' => $row->kegiatan ?? '',
                        'output' => $row->output ?? '',
                    ]];
                })->all();
            });

        $rows = [];
        $no = 0;

        foreach ($employees as $employee) {
            $no++;
            $filledDays = $filledByUser->get($employee->id, []);
            $days = [];
            $total = 0;

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $hasEntry = isset($filledDays[$day]);
                $days[$day] = [
                    'filled' => $hasEntry,
                    'kegiatan' => $hasEntry ? ($filledDays[$day]['kegiatan'] ?? '') : '',
                    'output' => $hasEntry ? ($filledDays[$day]['output'] ?? '') : '',
                ];
                $isHoliday = isset($holidays[$day]);
                if ($hasEntry && ! $isHoliday) {
                    $total++;
                }
            }

            $rows[] = [
                'no' => $no,
                'user_id' => $employee->id,
                'name' => $employee->name,
                'days' => $days,
                'total' => $total,
            ];
        }

        return view($this->view.'.table', [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'days_in_month' => $daysInMonth,
            'month_name' => $monthName,
            'holidays' => $holidays,
            'rows' => $rows,
        ]);
    }

    public function detail(Request $request, string $pegawaiId, int $bulan, int $tahun)
    {
        $auth = $request->user();

        $pegawai = $this->scopedPegawaiQuery($auth)
            ->where('id', $pegawaiId)
            ->firstOrFail();

        $rows = LKH::query()
            ->where('user_id', $pegawaiId)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->get();

        $labelBulan = Carbon::createFromFormat('m', str_pad((string) $bulan, 2, '0', STR_PAD_LEFT))
            ->locale('id')
            ->translatedFormat('F');

        return view($this->view.'.detail', compact('pegawai', 'rows', 'bulan', 'tahun', 'labelBulan'));
    }

    private function scopedPegawaiQuery(User $auth): Builder
    {
        $auth->loadMissing('access_group');

        $query = User::query()
            ->where('level_id', '!=', 1)
            ->where('id', '!=', $auth->id)
            ->orderByRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))");

        if (in_array($auth->access_group?->code ?? '', ['root', 'admin'], true) || (int) $auth->jabatan === 1) {
            return $query;
        }

        if ((int) $auth->jabatan === 2) {
            return $query->where('subbagian', $auth->subbagian);
        }

        abort(403, 'Anda tidak memiliki akses memantau LKH bawahan.');
    }
}
