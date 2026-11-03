<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LkhResource;
use App\Models\LKH;
use App\Models\LkhHoliday;
use App\Models\LkhPengajuan;
use App\Models\User;
use App\Services\LkhHolidayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class LkhController extends Controller
{
    /**
     * List LKH Per User
     *
     * Menampilkan daftar Laporan Kinerja Harian (LKH) untuk user yang sedang login.
     * Jika akun memiliki hak akses monitoring/atasan/admin, dapat melihat data LKH user lain dengan menyertakan query parameter `user_id`.
     *
     * @queryParam user_id string UUID User target (khusus atasan/admin, opsional). Example: 9c29f273-0498-472d-bf80-ecabda162b7e
     * @queryParam tanggal string Format Y-m-d untuk memfilter tanggal tertentu. Example: 2026-11-03
     * @queryParam bulan int Angka bulan (1-12) untuk memfilter berdasarkan bulan. Example: 11
     * @queryParam tahun int Tahun 4 digit untuk memfilter berdasarkan tahun. Example: 2026
     * @queryParam q string Kata kunci pencarian untuk kegiatan atau output. Example: verifikasi berkas
     * @queryParam limit int Jumlah data per halaman (default 20, max 100). Example: 20
     * @queryParam sort_by string Kolom pengurutan (tanggal, created_at, kegiatan, output). Default: tanggal. Example: tanggal
     * @queryParam order string Arah pengurutan (asc atau desc). Default: desc. Example: desc
     */
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $authUser = $request->user();
        $targetUserId = $request->query('user_id');

        if ($targetUserId && $targetUserId !== $authUser->id) {
            $targetUser = User::find($targetUserId);
            if (! $targetUser || ! $this->canAccessUserLkh($authUser, $targetUser)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Anda tidak memiliki hak akses untuk melihat LKH pengguna ini.',
                ], 403);
            }
        } else {
            $targetUserId = $authUser->id;
        }

        $limit = min(max((int) $request->query('limit', $request->query('per_page', 20)), 1), 100);
        $sortBy = in_array($request->query('sort_by'), ['tanggal', 'created_at', 'kegiatan', 'output'], true)
            ? $request->query('sort_by')
            : 'tanggal';
        $sortOrder = strtolower((string) $request->query('order', $request->query('sort_order', 'desc'))) === 'asc' ? 'asc' : 'desc';

        $query = LKH::query()
            ->with('user')
            ->where('user_id', $targetUserId);

        if ($request->filled('tanggal')) {
            $query->whereDate('tanggal', $request->query('tanggal'));
        }

        if ($request->filled('bulan')) {
            $query->whereMonth('tanggal', (int) $request->query('bulan'));
        }

        if ($request->filled('tahun')) {
            $query->whereYear('tanggal', (int) $request->query('tahun'));
        }

        if ($q = $request->query('q')) {
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('kegiatan', 'like', "%{$q}%")
                    ->orWhere('output', 'like', "%{$q}%");
            });
        }

        $lkhs = $query->orderBy($sortBy, $sortOrder)->paginate($limit);

        return LkhResource::collection($lkhs);
    }

    /**
     * Create LKH
     *
     * Menyimpan data Laporan Kinerja Harian (LKH) baru untuk user yang sedang login.
     */
    public function store(Request $request, LkhHolidayService $holidayService): JsonResponse
    {
        $authUser = $request->user();

        $validated = $request->validate([
            'tanggal' => 'required|date_format:Y-m-d',
            'kegiatan' => 'required|string',
            'output' => 'required|string',
            'user_id' => 'nullable|uuid|exists:users,id',
        ]);

        $userId = $authUser->id;
        if (! empty($validated['user_id']) && $this->isAdmin($authUser)) {
            $userId = $validated['user_id'];
        }

        // Cek duplikasi tanggal untuk user ini
        $exists = LKH::query()
            ->where('user_id', $userId)
            ->whereDate('tanggal', $validated['tanggal'])
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => false,
                'message' => 'Kegiatan pada tanggal tersebut sudah ada.',
                'errors' => [
                    'tanggal' => ['Kegiatan pada tanggal tersebut sudah ada.'],
                ],
            ], 422);
        }

        // Validasi hari kerja, bukan libur, dan tidak di masa depan
        $date = Carbon::parse($validated['tanggal']);
        if (! $holidayService->isSelectableTanggal($date)) {
            return response()->json([
                'status' => false,
                'message' => $holidayService->selectableTanggalValidationMessage(),
                'errors' => [
                    'tanggal' => [$holidayService->selectableTanggalValidationMessage()],
                ],
            ], 422);
        }

        // Cek apakah periode bulan pengajuan terkunci (pending / approved)
        $pengajuan = $this->pengajuanForUserTanggal($userId, $validated['tanggal']);
        if ($this->isLkhPeriodLockedForPengajuan($pengajuan)) {
            return response()->json([
                'status' => false,
                'message' => 'Periode bulan kegiatan ini dalam pengajuan atau sudah disetujui; tidak dapat menambah data.',
            ], 422);
        }

        $lkh = LKH::create([
            'user_id' => $userId,
            'tanggal' => $validated['tanggal'],
            'kegiatan' => $validated['kegiatan'],
            'output' => $validated['output'],
        ]);

        return (new LkhResource($lkh->loadMissing('user')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Get LKH
     *
     * Menampilkan detail satu data Laporan Kinerja Harian (LKH).
     */
    public function show(Request $request, LKH $lkh): JsonResponse
    {
        $authUser = $request->user();

        if (! $this->canAccessUserLkh($authUser, $lkh->user)) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki hak akses ke data LKH ini.',
            ], 403);
        }

        return (new LkhResource($lkh->loadMissing('user')))->response();
    }

    /**
     * Update LKH
     *
     * Mengubah data Laporan Kinerja Harian (LKH) milik user yang sedang login.
     */
    public function update(Request $request, LKH $lkh, LkhHolidayService $holidayService): JsonResponse
    {
        $authUser = $request->user();

        // Hanya pemilik data LKH atau admin/root yang boleh mengubah
        if ($lkh->user_id !== $authUser->id && ! $this->isAdmin($authUser)) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki hak akses untuk mengubah data LKH ini.',
            ], 403);
        }

        // Cek apakah periode tanggal lama terkunci
        $pOld = $this->pengajuanForUserTanggal($lkh->user_id, (string) $lkh->tanggal);
        if ($this->isLkhPeriodLockedForPengajuan($pOld)) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak dapat diubah karena laporan periode ini sedang diajukan atau sudah disetujui.',
            ], 422);
        }

        $validated = $request->validate([
            'tanggal' => 'sometimes|required|date_format:Y-m-d',
            'kegiatan' => 'sometimes|required|string',
            'output' => 'sometimes|required|string',
        ]);

        if (isset($validated['tanggal']) && $validated['tanggal'] !== (string) $lkh->tanggal) {
            // Cek duplikasi tanggal baru untuk user ini
            $exists = LKH::query()
                ->where('user_id', $lkh->user_id)
                ->whereDate('tanggal', $validated['tanggal'])
                ->where('id', '!=', $lkh->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kegiatan pada tanggal tersebut sudah ada.',
                    'errors' => [
                        'tanggal' => ['Kegiatan pada tanggal tersebut sudah ada.'],
                    ],
                ], 422);
            }

            // Validasi tanggal baru (hari kerja, bukan libur, dll)
            $newDate = Carbon::parse($validated['tanggal']);
            $oldDate = Carbon::parse($lkh->tanggal);
            if (! $holidayService->isSelectableTanggal($newDate, $oldDate)) {
                return response()->json([
                    'status' => false,
                    'message' => $holidayService->selectableTanggalValidationMessage(),
                    'errors' => [
                        'tanggal' => [$holidayService->selectableTanggalValidationMessage()],
                    ],
                ], 422);
            }

            // Cek apakah periode tanggal baru terkunci
            $pNew = $this->pengajuanForUserTanggal($lkh->user_id, $validated['tanggal']);
            if ($this->isLkhPeriodLockedForPengajuan($pNew)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tanggal yang dipilih berada pada periode yang sedang diajukan atau sudah disetujui.',
                ], 422);
            }
        }

        $lkh->update($validated);

        return (new LkhResource($lkh->fresh()->loadMissing('user')))->response();
    }

    /**
     * Delete LKH
     *
     * Menghapus data Laporan Kinerja Harian (LKH) milik user yang sedang login.
     */
    public function destroy(Request $request, LKH $lkh): JsonResponse
    {
        $authUser = $request->user();

        // Hanya pemilik data LKH atau admin/root yang boleh menghapus
        if ($lkh->user_id !== $authUser->id && ! $this->isAdmin($authUser)) {
            return response()->json([
                'status' => false,
                'message' => 'Anda tidak memiliki hak akses untuk menghapus data LKH ini.',
            ], 403);
        }

        // Cek apakah periode terkunci
        $p = $this->pengajuanForUserTanggal($lkh->user_id, (string) $lkh->tanggal);
        if ($this->isLkhPeriodLockedForPengajuan($p)) {
            return response()->json([
                'status' => false,
                'message' => 'Data tidak dapat dihapus karena laporan periode ini sedang diajukan atau sudah disetujui.',
            ], 422);
        }

        $lkh->delete();

        return response()->json([
            'status' => true,
            'message' => 'Data LKH berhasil dihapus.',
        ]);
    }

    /**
     * Config Datepicker LKH
     *
     * Mendapatkan tanggal maksimal pengisian (hari ini) dan daftar tanggal libur nasional terdaftar.
     */
    public function pickerConfig(LkhHolidayService $holidayService): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => [
                'maxDate' => now()->format('Y-m-d'),
                'holidays' => $holidayService->registeredHolidayDates(),
            ],
        ]);
    }

    /**
     * Cek Hari & Tanggal Merah
     *
     * Mendapatkan nama hari (Indonesia & Inggris) dari suatu tanggal, status apakah tanggal merah (libur nasional / akhir pekan), dan informasi apakah user sudah mengisi LKH di tanggal tersebut.
     *
     * @queryParam tanggal string required Format Y-m-d. Tanggal yang ingin dicek. Example: 2026-11-03
     */
    public function checkDate(Request $request, LkhHolidayService $holidayService): JsonResponse
    {
        $request->validate([
            'tanggal' => 'required|date_format:Y-m-d',
        ]);

        $tanggalStr = $request->query('tanggal');
        $date = Carbon::parse($tanggalStr);

        $authUser = $request->user();

        // Cari hari libur nasional di database
        $dbHoliday = LkhHoliday::query()
            ->whereDate('tanggal', $date->toDateString())
            ->first();

        $isWeekend = $date->isWeekend();
        $isDbHoliday = (bool) $dbHoliday;
        $isHoliday = $isWeekend || $isDbHoliday;

        $keterangan = null;
        if ($isDbHoliday) {
            $keterangan = $dbHoliday->keterangan ?: 'Hari Libur Nasional';
        } elseif ($isWeekend) {
            $keterangan = 'Akhir pekan ('.$date->locale('id')->translatedFormat('l').')';
        }

        $isFuture = $date->isAfter(now()->endOfDay());
        $isSelectable = $holidayService->isSelectableTanggal($date);

        // Cek apakah user yang sedang login sudah mengisi LKH pada tanggal ini
        $existingLkh = null;
        if ($authUser) {
            $existingLkh = LKH::query()
                ->where('user_id', $authUser->id)
                ->whereDate('tanggal', $tanggalStr)
                ->first();
        }

        return response()->json([
            'status' => true,
            'data' => [
                'tanggal' => $tanggalStr,
                'tanggal_formatted' => $date->locale('id')->translatedFormat('l, d F Y'),
                'hari' => $date->locale('id')->translatedFormat('l'),
                'hari_inggris' => $date->format('l'),
                'bulan_nama' => $date->locale('id')->translatedFormat('F'),
                'tahun' => (int) $date->format('Y'),
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
                'is_workday' => ! $isHoliday,
                'is_future' => $isFuture,
                'is_selectable' => $isSelectable,
                'keterangan' => $keterangan,
                'lkh_sudah_diisi' => (bool) $existingLkh,
                'lkh_id' => $existingLkh?->id,
            ],
        ]);
    }

    /**
     * List Tanggal Merah & Hari Libur
     *
     * Menampilkan daftar semua tanggal merah (hari libur nasional terdaftar dan/atau akhir pekan Sabtu-Minggu).
     *
     * @queryParam tahun int Tahun yang ingin dicek (default: tahun berjalan). Example: 2026
     * @queryParam bulan int Bulan yang ingin dicek (1-12, opsional). Example: 11
     * @queryParam include_weekend boolean Apakah akhir pekan (Sabtu & Minggu) disertakan (default: true). Example: true
     */
    public function holidays(Request $request): JsonResponse
    {
        $tahun = (int) $request->query('tahun', now()->year);
        $bulan = $request->has('bulan') ? (int) $request->query('bulan') : null;
        $includeWeekend = filter_var($request->query('include_weekend', true), FILTER_VALIDATE_BOOLEAN);

        // Ambil hari libur dari database
        $holidayQuery = LkhHoliday::query()
            ->whereYear('tanggal', $tahun);

        if ($bulan) {
            $holidayQuery->whereMonth('tanggal', $bulan);
        }

        $dbHolidays = $holidayQuery->orderBy('tanggal')->get();

        $holidaysMap = [];
        foreach ($dbHolidays as $row) {
            $key = $row->tanggal->format('Y-m-d');
            $date = Carbon::parse($row->tanggal);
            $holidaysMap[$key] = [
                'tanggal' => $key,
                'tanggal_formatted' => $date->locale('id')->translatedFormat('l, d F Y'),
                'hari' => $date->locale('id')->translatedFormat('l'),
                'hari_inggris' => $date->format('l'),
                'tipe' => 'libur_nasional',
                'keterangan' => $row->keterangan ?: 'Hari Libur Nasional',
                'is_weekend' => $date->isWeekend(),
            ];
        }

        // Jika akhir pekan disertakan, tambahkan semua Sabtu & Minggu
        if ($includeWeekend) {
            $startMonth = $bulan ?: 1;
            $endMonth = $bulan ?: 12;

            for ($m = $startMonth; $m <= $endMonth; $m++) {
                $daysInMonth = Carbon::create($tahun, $m, 1)->daysInMonth;
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $date = Carbon::create($tahun, $m, $d);
                    if ($date->isWeekend()) {
                        $key = $date->format('Y-m-d');
                        if (! isset($holidaysMap[$key])) {
                            $holidaysMap[$key] = [
                                'tanggal' => $key,
                                'tanggal_formatted' => $date->locale('id')->translatedFormat('l, d F Y'),
                                'hari' => $date->locale('id')->translatedFormat('l'),
                                'hari_inggris' => $date->format('l'),
                                'tipe' => 'akhir_pekan',
                                'keterangan' => 'Akhir pekan ('.$date->locale('id')->translatedFormat('l').')',
                                'is_weekend' => true,
                            ];
                        }
                    }
                }
            }
        }

        // Urutkan berdasarkan tanggal
        ksort($holidaysMap);
        $result = array_values($holidaysMap);

        return response()->json([
            'status' => true,
            'filter' => [
                'tahun' => $tahun,
                'bulan' => $bulan,
                'include_weekend' => $includeWeekend,
            ],
            'total' => count($result),
            'data' => $result,
        ]);
    }

    /**
     * Cek apakah user yang terautentikasi berhak melihat LKH user target.
     */
    private function canAccessUserLkh(User $authUser, ?User $targetUser): bool
    {
        if (! $targetUser) {
            return false;
        }

        if ($authUser->id === $targetUser->id) {
            return true;
        }

        if ($this->isAdmin($authUser)) {
            return true;
        }

        // Atasan langsung
        if ($targetUser->atasan_id === $authUser->id) {
            return true;
        }

        // Sekretaris (jabatan 1) memantau semua pegawai
        if ((int) $authUser->jabatan === 1) {
            return true;
        }

        // Kepala Subbagian (jabatan 2) memantau pegawai satu subbagian
        if ((int) $authUser->jabatan === 2 && ! empty($authUser->subbagian) && $authUser->subbagian === $targetUser->subbagian) {
            return true;
        }

        return false;
    }

    private function isAdmin(User $user): bool
    {
        $user->loadMissing('access_group');

        return in_array($user->access_group?->code ?? '', ['root', 'admin'], true);
    }

    private function pengajuanForUserTanggal(string $userId, string $tanggal): ?LkhPengajuan
    {
        $t = Carbon::parse($tanggal);

        return LkhPengajuan::query()
            ->where('user_id', $userId)
            ->where('bulan', (int) $t->format('n'))
            ->where('tahun', (int) $t->format('Y'))
            ->first();
    }

    private function isLkhPeriodLockedForPengajuan(?LkhPengajuan $p): bool
    {
        return $p && in_array($p->status, [
            LkhPengajuan::STATUS_PENDING,
            LkhPengajuan::STATUS_APPROVED,
        ], true);
    }
}
