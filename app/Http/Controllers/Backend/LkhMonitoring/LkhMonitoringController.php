<?php

namespace App\Http\Controllers\Backend\LkhMonitoring;

use App\Http\Controllers\Controller;
use App\Models\LKH;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

    public function data(Request $request)
    {
        $auth = $request->user();
        $bulan = (int) $request->input('bulan', now()->format('m'));
        $tahun = (int) $request->input('tahun', now()->format('Y'));

        $query = $this->scopedPegawaiQuery($auth);

        $users = $query->get();
        $userIds = $users->pluck('id')->all();

        $lkhRows = LKH::query()
            ->whereIn('user_id', $userIds)
            ->whereMonth('tanggal', $bulan)
            ->whereYear('tanggal', $tahun)
            ->orderBy('tanggal')
            ->get(['user_id', 'tanggal'])
            ->groupBy('user_id');

        return datatables()->of($query)
            ->filterColumn('nama_pegawai', function (Builder $q, $keyword) {
                $q->whereRaw(
                    '(CONCAT(COALESCE(first_name,\'\'),\' \',COALESCE(last_name,\'\'))) like ?',
                    ["%{$keyword}%"]
                );
            })
            ->addColumn('nama_pegawai', fn ($row) => e($row->name))
            ->addColumn('nip', fn ($row) => e($row->nip ?? '-'))
            ->addColumn('jumlah_lkh', function ($row) use ($lkhRows) {
                return (string) ($lkhRows->get($row->id)?->count() ?? 0);
            })
            ->addColumn('terakhir_input', function ($row) use ($lkhRows) {
                $rows = $lkhRows->get($row->id);
                if (! $rows || $rows->isEmpty()) {
                    return '—';
                }

                $last = $rows->max('tanggal');

                return Carbon::parse($last)->locale('id')->translatedFormat('d M Y');
            })
            ->addColumn('action', function ($row) use ($auth, $bulan, $tahun) {
                $btn = '';
                if ($auth->read) {
                    $btn .= '<button type="button" class="btn btn-sm btn-outline btn-lihat-monitoring" data-id="'.e($row->id).'" data-bulan="'.e((string) $bulan).'" data-tahun="'.e((string) $tahun).'" title="Lihat detail"><i class="fa fa-list-alt text-primary"></i></button>';
                }

                return "<div class='btn-group'>".$btn.'</div>';
            })
            ->rawColumns(['action'])
            ->addIndexColumn()
            ->make();
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
