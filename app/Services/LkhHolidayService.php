<?php

namespace App\Services;

use App\Models\LkhHoliday;
use Illuminate\Support\Carbon;

class LkhHolidayService
{
    /**
     * @return array<int, string> day number => label
     */
    public function holidaysInMonth(int $bulan, int $tahun): array
    {
        $daysInMonth = Carbon::create($tahun, $bulan, 1)->daysInMonth;
        $dbHolidays = $this->databaseHolidaysForMonth($bulan, $tahun);
        $result = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::create($tahun, $bulan, $day);
            $key = $date->format('Y-m-d');

            if (isset($dbHolidays[$key])) {
                $result[$day] = $dbHolidays[$key];

                continue;
            }

            if ($date->isWeekend()) {
                $result[$day] = 'Akhir pekan';
            }
        }

        return $result;
    }

    public function isHoliday(int $bulan, int $tahun, int $day): bool
    {
        return array_key_exists($day, $this->holidaysInMonth($bulan, $tahun));
    }

    /**
     * @return list<string> Tanggal libur terdaftar (Y-m-d).
     */
    public function registeredHolidayDates(): array
    {
        return LkhHoliday::query()
            ->orderBy('tanggal')
            ->get()
            ->map(fn (LkhHoliday $row) => $row->tanggal->format('Y-m-d'))
            ->values()
            ->all();
    }

    /**
     * Tanggal boleh dipilih untuk input LKH (bukan akhir pekan, bukan libur DB, tidak setelah hari ini).
     */
    public function isSelectableTanggal(Carbon $date, ?Carbon $exceptSameDay = null): bool
    {
        if ($exceptSameDay !== null && $date->isSameDay($exceptSameDay)) {
            return true;
        }

        if ($date->isAfter(now()->endOfDay())) {
            return false;
        }

        if ($date->isWeekend()) {
            return false;
        }

        return ! LkhHoliday::query()->whereDate('tanggal', $date->toDateString())->exists();
    }

    public function selectableTanggalValidationMessage(): string
    {
        return 'Tanggal tidak valid. Pilih hari kerja (bukan Sabtu/Minggu), bukan hari libur, dan tidak setelah hari ini.';
    }

    /**
     * @return array<string, string> Y-m-d => keterangan
     */
    private function databaseHolidaysForMonth(int $bulan, int $tahun): array
    {
        return LkhHoliday::query()
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->get()
            ->mapWithKeys(fn (LkhHoliday $row) => [
                $row->tanggal->format('Y-m-d') => $row->keterangan ?: 'Libur',
            ])
            ->all();
    }
}
