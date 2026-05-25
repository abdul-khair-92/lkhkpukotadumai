<?php

namespace App\Http\Controllers\Backend\LkhHariLibur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LkhHariLiburController extends Controller
{
    public function index()
    {
        return view($this->view.'.index');
    }

    public function create()
    {
        return view($this->view.'.create');
    }

    public function data(Request $request)
    {
        $query = $this->model::query()->orderByDesc('tanggal');
        $user = $request->user();

        return datatables()->of($query)
            ->editColumn('tanggal', fn ($row) => $row->tanggal
                ? $row->tanggal->locale('id')->translatedFormat('d F Y')
                : '—')
            ->editColumn('keterangan', fn ($row) => e($row->keterangan ?: '—'))
            ->addColumn('action', function ($row) use ($user) {
                $button = '';
                if ($user->update) {
                    $button .= '<button class="btn-action btn btn-sm btn-outline" data-title="Edit" data-action="edit" data-url="'.$this->url.'" data-id="'.$row->id.'" title="Edit"><i class="fa fa-edit text-warning"></i></button> ';
                }
                if ($user->delete) {
                    $button .= '<button class="btn-action btn btn-sm btn-outline" data-title="Hapus" data-action="delete" data-url="'.$this->url.'" data-id="'.$row->id.'" title="Hapus"><i class="fa fa-trash text-danger"></i></button>';
                }

                return '<'.'div class="btn-group">'. $button .'</'.'div>';

            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make();
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:lkh_holidays,tanggal,NULL,id,deleted_at,NULL',
            'keterangan' => 'nullable|string|max:255',
        ]);

        if ($this->model::create($request->only('tanggal', 'keterangan'))) {
            return response()->json(['status' => true, 'message' => 'Hari libur berhasil disimpan']);
        }

        return response()->json(['status' => false, 'message' => 'Hari libur gagal disimpan']);
    }

    public function edit($id)
    {
        return view($this->view.'.edit', [
            'data' => $this->model::findOrFail($id),
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date|unique:lkh_holidays,tanggal,'.$id.',id,deleted_at,NULL',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $data = $this->model::findOrFail($id);
        if ($data->update($request->only('tanggal', 'keterangan'))) {
            return response()->json(['status' => true, 'message' => 'Hari libur berhasil diperbarui']);
        }

        return response()->json(['status' => false, 'message' => 'Hari libur gagal diperbarui']);
    }

    public function delete($id)
    {
        $data = $this->model::findOrFail($id);

        return view($this->view.'.delete', compact('data'));
    }

    public function destroy($id)
    {
        $data = $this->model::findOrFail($id);
        if ($data->delete()) {
            return response()->json(['status' => true, 'message' => 'Hari libur berhasil dihapus']);
        }

        return response()->json(['status' => false, 'message' => 'Hari libur gagal dihapus']);
    }
}
