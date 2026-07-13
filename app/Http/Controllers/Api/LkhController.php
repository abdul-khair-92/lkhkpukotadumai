<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LkhResource;
use App\Models\LKH;
use Illuminate\Http\Request;

class LkhController extends Controller
{
    /**
     * List LKH
     *
     * Menampilkan daftar semua data Laporan Kinerja Harian (LKH).
     */
    public function index(Request $request)
    {
        $limit = $request->query('limit', 20);
        $q = $request->query('q');

        $query = LKH::query();

        if ($q) {
            $query->where('kegiatan', 'like', "%{$q}%")
                  ->orWhere('output', 'like', "%{$q}%");
        }

        $lkhs = $query->paginate($limit);

        return LkhResource::collection($lkhs);
    }

    /**
     * Create LKH
     *
     * Menyimpan data Laporan Kinerja Harian (LKH) baru.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'kegiatan' => 'required|string',
            'output' => 'required|string',
        ]);

        $validated['user_id'] = $request->user()?->id;

        $lkh = LKH::create($validated);

        return new LkhResource($lkh);
    }

    /**
     * Get LKH
     *
     * Menampilkan detail satu data Laporan Kinerja Harian (LKH).
     */
    public function show(LKH $lkh)
    {
        return new LkhResource($lkh);
    }

    /**
     * Update LKH
     *
     * Mengubah data Laporan Kinerja Harian (LKH) yang sudah ada.
     */
    public function update(Request $request, LKH $lkh)
    {
        $validated = $request->validate([
            'tanggal' => 'sometimes|required|date',
            'kegiatan' => 'sometimes|required|string',
            'output' => 'sometimes|required|string',
        ]);

        $lkh->update($validated);

        return new LkhResource($lkh);
    }

    /**
     * Delete LKH
     *
     * Menghapus data Laporan Kinerja Harian (LKH).
     */
    public function destroy(LKH $lkh)
    {
        $lkh->delete();

        return response()->json([
            'message' => 'LKH deleted successfully'
        ]);
    }
}
