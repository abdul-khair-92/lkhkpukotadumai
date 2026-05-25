<?php

namespace App\Http\Controllers\Backend\User;

use App\Http\Controllers\Controller;
use App\Models\AccessGroup;
use App\Models\Level;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        return view($this->view.'.index');
    }

    public function create()
    {
        $atasanJabatan = [1, 2];
        $atasan = $this->model::whereNotNull('jabatan')
            ->where('jabatan', '!=', '')
            ->whereIn('jabatan', $atasanJabatan)
            ->get()
            ->pluck('name', 'id');
        $atasan = $atasan->prepend('- Pilih Atasan -', '');
        $level = Level::filterLevel()->pluck('name', 'id');
        $access_group = AccessGroup::filterLevel()->pluck('name', 'id');

        return view($this->view.'.create', compact('atasan', 'level', 'access_group'));
    }

    public function data(Request $request)
    {
        $data = $this->model::filterLevel()->with('level', 'access_group');
        $user = $request->user();

        return datatables()->of($data)
            ->filterColumn('name', function ($query, $keyword) {
                $sql = "CONCAT(first_name,' ',last_name)  like ?";
                $query->whereRaw($sql, ["%{$keyword}%"]);
            })
            ->addColumn('action', function ($data) use ($user) {
                $button = '';
                if ($user->update) {
                    $button .= '<button class="btn-action btn btn-sm btn-outline" data-title="Edit" data-action="edit" data-url="'.$this->url.'" data-id="'.$data->id.'" title="Edit"> <i class="fa fa-edit text-warning"></i> </button> ';
                }
                if ($user->delete) {
                    $button .= '<button class="btn-action btn btn-sm btn-outline" data-title="Delete" data-action="delete" data-url="'.$this->url.'" data-id="'.$data->id.'" title="Delete"> <i class="fa fa-trash text-danger"></i> </button>';
                }

                return "<div class='btn-group'>".$button.'</div>';
            })
            ->addIndexColumn()
            ->rawColumns(['action'])
            ->make();
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|min:2',
            'last_name' => 'nullable|min:3',
            'nip' => 'required',
            'jabatan' => 'required',
            'subbagian' => 'required',
            'atasan_id' => 'nullable|exists:users,id',
            'email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|min:8|same:password',
            'level_id' => 'required|exists:levels,id',
            'access_group_id' => 'required|exists:access_groups,id',
        ]);
        if ($this->model::create($request->all())) {
            $response = ['status' => true, 'message' => 'Data berhasil disimpan'];
        }

        return response()->json($response ?? ['status' => false, 'message' => 'Data gagal disimpan']);
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $data = $this->model::findOrFail($id);
        $atasanJabatan = [0, 1];
        $atasan = $this->model::whereNotNull('jabatan')
            ->where('jabatan', '!=', '')
            ->whereIn('jabatan', $atasanJabatan)
            ->where('id', '!=', $id)
            ->get()
            ->pluck('name', 'id');
        $atasan = $atasan->prepend('- Pilih Atasan -', '');
        $level = Level::filterLevel()->pluck('name', 'id');
        $access_group = AccessGroup::filterLevel()->pluck('name', 'id');

        return view($this->view.'.edit', compact('data', 'atasan', 'level', 'access_group'));
    }

    public function update(Request $request, $id)
    {
        $authUser = $request->user();
        $isSelfEdit = $authUser && (string) $authUser->id === (string) $id;
        $is_required = $request->password ? 'required' : 'nullable';

        $rules = [
            'first_name' => 'required|min:2',
            'last_name' => 'nullable|min:3',
            'email' => 'required|email|unique:users,email,'.$id.',id,deleted_at,NULL',
            'password' => $is_required.'|min:8|confirmed',
            'password_confirmation' => $is_required.'|min:8|same:password',
        ];

        if (! $isSelfEdit) {
            $rules['nip'] = 'required';
            $rules['jabatan'] = 'required';
            $rules['subbagian'] = 'required';
            $rules['atasan_id'] = 'nullable|exists:users,id';
            $rules['level_id'] = 'nullable|exists:levels,id';
            $rules['access_group_id'] = 'nullable|exists:access_groups,id';
        }

        $request->validate($rules);

        $data = $this->model::findOrFail($id);

        $payload = $request->only(['first_name', 'last_name', 'email']);

        if ($request->filled('password')) {
            $payload['password'] = $request->password;
        }

        if (! $isSelfEdit) {
            $payload = array_merge($payload, $request->only([
                'nip', 'jabatan', 'subbagian', 'atasan_id', 'level_id', 'access_group_id',
            ]));
        }

        if ($data->update($payload)) {
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
