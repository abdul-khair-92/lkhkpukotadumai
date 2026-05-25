<?php

namespace App\Services;

use App\Models\LKH;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class LkhRekapService
{
    public function __construct(
        private readonly LkhHolidayService $holidayService
    ) {}

    /**
     * @return array{
     *     bulan: int,
     *     tahun: int,
     *     days_in_month: int,
     *     month_name: string,
     *     holidays: array<int, string>,
     *     rows: list<array{no: int, user_id: string, name: string, days: array<int, bool>, total: int}>
     * }
     */
    public function build(int $bulan, int $tahun, User $authUser): array
    {
        $bulan = max(1, min(12, $bulan));
        $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;
        $monthName = Carbon::create($tahun, $bulan, 1)->locale('id')->translatedFormat('F Y');
        $holidays = $this->holidayService->holidaysInMonth($bulan, $tahun);

        $employees = $this->employeesQuery($authUser)
            ->get()
            ->sort(fn (User $a, User $b) => $this->compareEmployeesForRekap($a, $b))
            ->values();
        $userIds = $employees->pluck('id');

        $filledByUser = LKH::query()
            ->whereIn('user_id', $userIds)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->get(['user_id', 'tanggal'])
            ->groupBy('user_id')
            ->map(function (Collection $rows) {
                return $rows
                    ->map(fn ($row) => (int) Carbon::parse($row->tanggal)->day)
                    ->unique()
                    ->flip()
                    ->all();
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
                $days[$day] = $hasEntry;
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

        return [
            'bulan' => $bulan,
            'tahun' => $tahun,
            'days_in_month' => $daysInMonth,
            'month_name' => $monthName,
            'holidays' => $holidays,
            'rows' => $rows,
            'signatories' => $this->resolveSignatories(),
        ];
    }

    /**
     * @return array{kasubbag: ?User, sekretaris: ?User, kasubbag_title: string, kasubbag_subtitle: string, sekretaris_title: string}
     */
    public function resolveSignatories(): array
    {
        $cfg = config('lkh.signatory', []);
        $kasubCfg = $cfg['kasubbag'] ?? [];
        $sekCfg = $cfg['sekretaris'] ?? [];

        return [
            'kasubbag' => $this->resolveSignatoryUser($kasubCfg),
            'sekretaris' => $this->resolveSignatoryUser($sekCfg),
            'kasubbag_title' => $kasubCfg['title'] ?? 'Kepala Subbagian',
            'kasubbag_subtitle' => $kasubCfg['subtitle'] ?? '',
            'sekretaris_title' => $sekCfg['title'] ?? 'Sekretaris',
        ];
    }

    /**
     * @param  array<string, mixed>  $criteria
     */
    private function resolveSignatoryUser(array $criteria): ?User
    {
        $jabatanList = config('master.app.jabatan', []);
        $subbagianList = config('master.app.subbagian', []);

        $jabatanValue = $this->masterListValue($jabatanList, $criteria['jabatan_label'] ?? null, $criteria['jabatan'] ?? null);
        $subbagianValue = $this->masterListValue($subbagianList, $criteria['subbagian_label'] ?? null, $criteria['subbagian'] ?? null);

        $query = User::query()
            ->where('level_id', '!=', 1)
            ->whereNotNull('jabatan')
            ->where('jabatan', '!=', '')
            ->where('jabatan', '!=', '0');

        if ($jabatanValue !== null) {
            $query->where('jabatan', (string) $jabatanValue);
        }

        if ($subbagianValue !== null) {
            $query->where('subbagian', (string) $subbagianValue);
        }

        return $query->orderBy('first_name')->first();
    }

    /**
     * @param  array<int|string, string>  $list
     */
    private function masterListValue(array $list, ?string $label, mixed $explicit = null): ?string
    {
        if ($explicit !== null && $explicit !== '') {
            return (string) $explicit;
        }

        if ($label === null || $label === '') {
            return null;
        }

        $key = array_search($label, $list, true);

        return $key !== false ? (string) $key : null;
    }

    private function employeesQuery(User $authUser)
    {
        $authUser->loadMissing('access_group');

        $query = User::query()
            ->where('level_id', '!=', 1)
            ->whereNotNull('jabatan')
            ->where('jabatan', '!=', '')
            ->where('jabatan', '!=', '0');

        if (! in_array($authUser->access_group->code ?? '', ['root', 'admin'], true)) {
            $query->where('atasan_id', $authUser->id);
        }

        return $query;
    }

    /**
     * Urutan descending tingkat jabatan: Sekretaris (1) → Kepala Subbagian (2) → Staf (3),
     * lalu nama A–Z dalam jabatan yang sama.
     */
    private function compareEmployeesForRekap(User $a, User $b): int
    {
        $rankA = $this->resolveJabatanIndex($a->jabatan);
        $rankB = $this->resolveJabatanIndex($b->jabatan);

        if ($rankA !== $rankB) {
            return $rankA <=> $rankB;
        }

        return strcasecmp($a->name, $b->name);
    }

    /**
     * Indeks jabatan dari master.app.jabatan (1 = Sekretaris … 3 = Staf).
     */
    private function resolveJabatanIndex(mixed $jabatan): int
    {
        if ($jabatan === null || $jabatan === '' || $jabatan === '0') {
            return 99;
        }

        if (is_numeric($jabatan)) {
            $numeric = (int) $jabatan;
            if ($numeric >= 1 && $numeric <= 10) {
                return $numeric;
            }
        }

        $list = config('master.app.jabatan', []);
        foreach ($list as $key => $label) {
            if ((int) $key < 1) {
                continue;
            }
            if (strcasecmp((string) $jabatan, (string) $label) === 0) {
                return (int) $key;
            }
        }

        return 99;
    }
}
