<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * Login API
     *
     * Autentikasi user dengan Email atau NIP dan password, menghasilkan Bearer token Sanctum.
     */
    public function login(Request $request): JsonResponse
    {
        $login = trim((string) $request->input('login', $request->input('email', '')));

        $request->merge(['login' => $login]);

        $request->validate([
            'login' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $value = trim((string) $value);
                    $exists = User::query()
                        ->whereNull('deleted_at')
                        ->where(function ($q) use ($value) {
                            $q->where('email', $value)->orWhere('nip', $value);
                        })
                        ->exists();
                    if (! $exists) {
                        $fail('Email atau NIP tidak terdaftar.');
                    }
                },
            ],
            'password' => 'required|string',
        ]);

        $credentials = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? ['email' => $login, 'password' => $request->password]
            : ['nip' => $login, 'password' => $request->password];

        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            $token = $user->createToken(uniqid().now())->plainTextToken;
            $response = [
                'status' => true,
                'message' => 'Login success',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ],
            ];

            return response()->json($response);
        }

        return response()->json([
            'status' => false,
            'message' => 'Password yang Anda masukkan salah.',
        ], 401);
    }

    /**
     * Logout API
     *
     * Mencabut token akses Sanctum yang sedang aktif.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'status' => true,
            'message' => 'Logout success',
        ]);
    }
}
