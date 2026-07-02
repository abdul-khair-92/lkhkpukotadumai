<?php

namespace App\Services;

use App\Models\LKH;
use App\Models\LkhPengajuan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LkhDashboardService
{
    public function __construct(
        private readonly LkhHolidayService $holidayService
    ) {}

    /**
     * @return array{
     *     is_atasan: bool,
     *     month_label: string,
     *     summary: array<string, mixed>,
     *     today: array<string, mixed>,
     *     calendar: array<string, mixed>,
     *     recent: list<array<string, mixed>>
     * }
     */
    public function build(User $user, ?int $bulan = null, ?int $tahun = null): array
    {
        $user->loadMissing('access_group');
        $isAdministrator = $this->isAdministrator($user);
        $isAtasan = $user->canAccessLkhPengajuan() && ! $isAdministrator;
        $bulan = $bulan ?? (int) now()->format('m');
        $tahun = $tahun ?? (int) now()->format('Y');

        return [
            'is_atasan' => $isAtasan,
            'month_label' => Carbon::create($tahun, $bulan, 1)->locale('id')->translatedFormat('F Y'),
            'summary' => $isAtasan
                ? $this->buildAtasanSummary($user, $bulan, $tahun)
                : $this->buildPegawaiSummary($user, $bulan, $tahun),
            'today' => $isAtasan
                ? $this->buildAtasanToday($user)
                : $this->buildPegawaiToday($user),
            'calendar' => $this->buildCalendar($user, $bulan, $tahun),
            'recent' => $isAtasan
                ? $this->buildAtasanRecent($user)
                : $this->buildPegawaiRecent($user),
        ];
    }

    private function isAdministrator(User $user): bool
    {
        return in_array($user->access_group?->code ?? '', ['root', 'admin'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPegawaiSummary(User $user, int $bulan, int $tahun): array
    {
        $holidays = $this->holidayService->holidaysInMonth($bulan, $tahun);
        $workingDays = $this->workingDaysInRange($bulan, $tahun, 1, $this->effectiveLastDay($bulan, $tahun), $holidays);

        $filledDates = LKH::query()
            ->where('user_id', $user->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('tanggal')
            ->map(fn ($d) => Carbon::parse($d)->day)
            ->unique();

        $terisi = $filledDates->filter(fn (int $day) => ! isset($holidays[$day]))->count();
        $belum = max(0, $workingDays - $terisi);
        $persen = $workingDays > 0 ? (int) round(($terisi / $workingDays) * 100) : 0;

        $pengajuan = LkhPengajuan::query()
            ->where('user_id', $user->id)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->first();

        return [
            'mode' => 'pegawai',
            'terisi' => $terisi,
            'belum' => $belum,
            'hari_kerja' => $workingDays,
            'persen' => $persen,
            'pengajuan' => $this->formatPengajuan($pengajuan),
            'hide_pengajuan' => (int) $user->jabatan === 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAtasanSummary(User $user, int $bulan, int $tahun): array
    {
        $bawahanIds = $this->scopedBawahanIds($user);

        $terisiTim = 0;
        $belumHariIni = 0;
        $pendingPengajuan = 0;

        if ($bawahanIds->isNotEmpty()) {
            $holidays = $this->holidayService->holidaysInMonth($bulan, $tahun);
            $today = now();
            $isTodayHoliday = $today->month === $bulan
                && $today->year === $tahun
                && $this->holidayService->isHoliday($bulan, $tahun, $today->day);

            $lkhByUser = LKH::query()
                ->whereIn('user_id', $bawahanIds)
                ->whereMonth('tanggal', $bulan)
                ->whereYear('tanggal', $tahun)
                ->get(['user_id', 'tanggal'])
                ->groupBy('user_id');

            foreach ($bawahanIds as $id) {
                $days = $lkhByUser->get($id, collect())
                    ->map(fn ($row) => (int) Carbon::parse($row->tanggal)->day)
                    ->unique()
                    ->filter(fn (int $day) => ! isset($holidays[$day]));
                $terisiTim += $days->count();
            }

            if (! $isTodayHoliday && $today->month === $bulan && $today->year === $tahun) {
                $filledToday = LKH::query()
                    ->whereIn('user_id', $bawahanIds)
                    ->whereDate('tanggal', $today->toDateString())
                    ->pluck('user_id')
                    ->unique();
                $belumHariIni = $bawahanIds->diff($filledToday)->count();
            }

            $pendingPengajuanQuery = LkhPengajuan::query()
                ->where('status', LkhPengajuan::STATUS_PENDING);

            if (! in_array($user->access_group?->code ?? '', ['root', 'admin'], true)) {
                $pendingPengajuanQuery->whereHas('user', function ($q) use ($user) {
                    $q->where('atasan_id', $user->id);
                });
            }
            $pendingPengajuan = $pendingPengajuanQuery->count();
        }

        $own = $this->buildPegawaiSummary($user, $bulan, $tahun);

        return [
            'mode' => 'atasan',
            'jumlah_bawahan' => $bawahanIds->count(),
            'terisi_tim' => $terisiTim,
            'belum_isi_hari_ini' => $belumHariIni,
            'pending_pengajuan' => $pendingPengajuan,
            'own' => $own,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPegawaiToday(User $user): array
    {
        $today = now();
        $bulan = (int) $today->format('m');
        $tahun = (int) $today->format('Y');
        $isHoliday = $this->holidayService->isHoliday($bulan, $tahun, $today->day);
        $isFilled = LKH::query()
            ->where('user_id', $user->id)
            ->whereDate('tanggal', $today->toDateString())
            ->exists();

        return [
            'is_holiday' => $isHoliday,
            'is_filled' => $isFilled,
            'holiday_label' => $isHoliday
                ? ($this->holidayService->holidaysInMonth($bulan, $tahun)[$today->day] ?? 'Hari libur')
                : null,
            'date_label' => $today->locale('id')->translatedFormat('l, d F Y'),
            'lkh_url' => route('l-k-h.index'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAtasanToday(User $user): array
    {
        $pegawai = $this->buildPegawaiToday($user);
        $bawahanIds = $this->scopedBawahanIds($user);
        $today = now();
        $bulan = (int) $today->format('m');
        $tahun = (int) $today->format('Y');
        $isHoliday = $this->holidayService->isHoliday($bulan, $tahun, $today->day);

        $belumHariIni = 0;
        if ($bawahanIds->isNotEmpty() && ! $isHoliday) {
            $filledToday = LKH::query()
                ->whereIn('user_id', $bawahanIds)
                ->whereDate('tanggal', $today->toDateString())
                ->pluck('user_id')
                ->unique();
            $belumHariIni = $bawahanIds->diff($filledToday)->count();
        }

        $pendingPengajuanQuery = LkhPengajuan::query()
            ->where('status', LkhPengajuan::STATUS_PENDING);

        if (! in_array($user->access_group?->code ?? '', ['root', 'admin'], true)) {
            $pendingPengajuanQuery->whereHas('user', function ($q) use ($user) {
                $q->where('atasan_id', $user->id);
            });
        }
        $pendingPengajuan = $pendingPengajuanQuery->count();

        return array_merge($pegawai, [
            'jumlah_bawahan' => $bawahanIds->count(),
            'bawahan_belum_hari_ini' => $belumHariIni,
            'pending_pengajuan' => $pendingPengajuan,
            'pengajuan_url' => route('lkh-pengajuan.index'),
            'monitoring_url' => $user->canAccessLkhMonitoring() ? route('lkh-monitoring.index') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCalendar(User $user, int $bulan, int $tahun): array
    {
        $holidays = $this->holidayService->holidaysInMonth($bulan, $tahun);
        $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;
        $filledDays = LKH::query()
            ->where('user_id', $user->id)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->pluck('tanggal')
            ->map(fn ($d) => (int) Carbon::parse($d)->day)
            ->flip()
            ->all();

        $today = now();
        $firstWeekday = Carbon::create($tahun, $bulan, 1)->dayOfWeekIso;
        $cells = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($tahun, $bulan, $day);
            $isFuture = $date->isAfter($today);
            $isToday = $date->isSameDay($today);

            if (isset($holidays[$day])) {
                $status = 'holiday';
            } elseif ($isFuture) {
                $status = 'future';
            } elseif (isset($filledDays[$day])) {
                $status = 'filled';
            } else {
                $status = 'empty';
            }

            $cells[] = [
                'day' => $day,
                'status' => $status,
                'is_today' => $isToday,
                'label' => $holidays[$day] ?? null,
            ];
        }

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'days_in_month' => $daysInMonth,
            'first_weekday' => $firstWeekday,
            'cells' => $cells,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildPegawaiRecent(User $user): array
    {
        return LKH::query()
            ->where('user_id', $user->id)
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn (LKH $row) => [
                'type' => 'lkh',
                'tanggal' => Carbon::parse($row->tanggal)->locale('id')->translatedFormat('d M Y'),
                'kegiatan' => (string) $row->kegiatan,
                'output' => (string) $row->output,
                'url' => route('l-k-h.index'),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildAtasanRecent(User $user): array
    {
        $query = LkhPengajuan::query()
            ->with('user')
            ->orderByDesc('updated_at')
            ->limit(5);

        $user->loadMissing('access_group');
        if (! in_array($user->access_group?->code ?? '', ['root', 'admin'], true)) {
            $query->whereHas('user', function ($q) use ($user) {
                $q->where('atasan_id', $user->id);
            });
        }

        return $query->get()->map(function (LkhPengajuan $row) {
            $bulanLabel = Carbon::create($row->tahun, $row->bulan, 1)
                ->locale('id')
                ->translatedFormat('F Y');

            return [
                'type' => 'pengajuan',
                'nama' => $row->user?->name ?? '—',
                'periode' => $bulanLabel,
                'status' => $row->status,
                'status_label' => $this->statusLabelText($row->status),
                'status_class' => $this->statusBadgeClass($row->status),
                'url' => route('lkh-pengajuan.index'),
            ];
        })->all();
    }

    /**
     * @return Collection<int, string>
     */
    private function scopedBawahanIds(User $auth): Collection
    {
        $auth->loadMissing('access_group');

        if (in_array($auth->access_group?->code ?? '', ['root', 'admin'], true)) {
            return User::query()
                ->where('level_id', '!=', 1)
                ->where('id', '!=', $auth->id)
                ->pluck('id');
        }

        if ((int) $auth->jabatan === 1) {
            return User::query()
                ->where('level_id', '!=', 1)
                ->where('id', '!=', $auth->id)
                ->pluck('id');
        }

        if ((int) $auth->jabatan === 2) {
            return User::query()
                ->where('level_id', '!=', 1)
                ->where('id', '!=', $auth->id)
                ->where('subbagian', $auth->subbagian)
                ->pluck('id');
        }

        return User::query()
            ->where('atasan_id', $auth->id)
            ->pluck('id');
    }

    private function effectiveLastDay(int $bulan, int $tahun): int
    {
        $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;
        $now = now();

        if ($bulan > (int) $now->format('m') && $tahun >= (int) $now->format('Y')) {
            return 0;
        }
        if ($bulan < (int) $now->format('m') && $tahun <= (int) $now->format('Y')) {
            return $daysInMonth;
        }
        if ($tahun < (int) $now->format('Y')) {
            return $daysInMonth;
        }

        return min($now->day, $daysInMonth);
    }

    /**
     * @param  array<int, string>  $holidays
     */
    private function workingDaysInRange(int $bulan, int $tahun, int $fromDay, int $toDay, array $holidays): int
    {
        if ($toDay < $fromDay) {
            return 0;
        }

        $count = 0;
        for ($day = $fromDay; $day <= $toDay; $day++) {
            if (! isset($holidays[$day])) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return array{status: ?string, label: string, badge_class: string}|null
     */
    private function formatPengajuan(?LkhPengajuan $p): ?array
    {
        if (! $p) {
            return [
                'status' => null,
                'label' => 'Belum mengajukan',
                'badge_class' => 'badge-secondary',
            ];
        }

        return [
            'status' => $p->status,
            'label' => $this->statusLabelText($p->status),
            'badge_class' => $this->statusBadgeClass($p->status),
        ];
    }

    private function statusLabelText(string $status): string
    {
        return match ($status) {
            LkhPengajuan::STATUS_APPROVED => 'Disetujui',
            LkhPengajuan::STATUS_REVISION => 'Revisi',
            LkhPengajuan::STATUS_CANCELLED => 'Dibatalkan',
            LkhPengajuan::STATUS_PENDING => 'Menunggu persetujuan',
            default => $status,
        };
    }

    private function statusBadgeClass(string $status): string
    {
        return match ($status) {
            LkhPengajuan::STATUS_APPROVED => 'badge-success',
            LkhPengajuan::STATUS_REVISION => 'badge-danger',
            LkhPengajuan::STATUS_CANCELLED => 'badge-secondary',
            LkhPengajuan::STATUS_PENDING => 'badge-warning',
            default => 'badge-light',
        };
    }
}
