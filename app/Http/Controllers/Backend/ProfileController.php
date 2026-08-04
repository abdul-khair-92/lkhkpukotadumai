<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function index()
    {
        return view('backend.profile.index');
    }

    public function apiDocs()
    {
        return view('backend.api-docs.index');
    }

    public function updatePhoto(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = auth()->user();

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

            // Simpan foto baru ke public disk (storage/app/public/photos)
            $path = $file->storeAs('photos', $filename, 'public');

            // Hapus foto lama jika ada
            if ($user->photo && Storage::disk('public')->exists($user->photo)) {
                Storage::disk('public')->delete($user->photo);
            }

            // Simpan path ke database
            $user->update([
                'photo' => $path
            ]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Foto profil berhasil diperbarui.',
                    'photo_url' => asset('storage/' . $path)
                ]);
            }

            return back()->with('success', 'Foto profil berhasil diperbarui.');
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengunggah foto.'
            ], 400);
        }

        return back()->with('error', 'Terjadi kesalahan saat mengunggah foto.');
    }
}
