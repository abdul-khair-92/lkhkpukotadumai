<?php

namespace App\Http\Controllers\Backend\Jabatan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class JabatanController extends Controller
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
        $user = $request->user();
        $data = $this->model::all();
        
        return datatables()->of($data)
            ->addColumn('action', function ($data) use ($user) {
                $button = '';
                if ($user->update) {
                    $button .= '<button class="btn-action btn btn-sm btn-outline" data-title="Edit" data-action="edit" data-url="'.$this->url.'" data-id="'.$data->id.'" title="Edit"> <i class="fa fa-edit text-warning"></i> </button> ';
                }
                if ($user->delete) {
                    $button .= '<button class="btn-action btn btn-sm btn-outline" data-title="Delete" data-action="delete" data-url="'.$this->url.'" data-id="'.$data->id.'" title="Delete"> <i class="fa fa-trash text-danger"></i> </button>';
                }
                return "<div class='btn-group'>".$button."</div>";
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make();
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:jabatans',
        ]);
        
        if ($this->model::create($request->all())) {
            $response = ['status' => true, 'message' => 'Data berhasil disimpan'];
        }
        return response()->json($response ?? ['status' => false, 'message' => 'Data gagal disimpan']);
    }

    public function show($id)
    {
        $data = $this->model::find($id);
        return view($this->view.'.show', compact('data'));
    }

    public function edit($id)
    {
        $data = $this->model::find($id);
        return view($this->view.'.edit', compact('data'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|unique:jabatans,name,' . $id . ',id',
        ]);
        
        $data = $this->model::find($id);
        if ($data->update($request->all())) {
            $response = ['status' => true, 'message' => 'Data berhasil disimpan'];
        }
        return response()->json($response ?? ['status' => false, 'message' => 'Data gagal disimpan']);
    }

    public function delete($id)
    {
        $data = $this->model::find($id);
        return view($this->view.'.delete', compact('data'));
    }

    public function destroy($id)
    {
        $data = $this->model::find($id);
        if ($data->delete()) {
            $response = ['status' => true, 'message' => 'Data berhasil dihapus'];
        }
        return response()->json($response ?? ['status' => false, 'message' => 'Data gagal dihapus']);
    }
}
