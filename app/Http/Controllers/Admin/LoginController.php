<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Validasi input
            if (empty($request->email)) {
                return response()->json([
                    'message' => 'Email tidak boleh kosong'
                ], 422);
            }

            if (empty($request->password)) {
                return response()->json([
                    'message' => 'Password tidak boleh kosong'
                ], 422);
            }

            // Validasi format
            $request->validate([
                'email' => 'email',
                'password' => 'string|min:8',
            ]);

            // Cek email ada atau tidak
            $admin = Admin::where('email', $request->email)->first();
            if (!$admin) {
                return response()->json([
                    'message' => 'Email tidak terdaftar'
                ], 404);
            }

            // Cek password
            if (!Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'message' => 'Password salah'
                ], 401);
            }

            $token = $admin->createToken('AdminToken')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'token' => $token,
                'admin' => $admin
            ], 200);

        } catch (\Exception $e) {
            // Jika ada error validasi format
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Format input tidak valid',
                    'errors' => $e->errors()
                ], 422);
            }

            return response()->json([
                'message' => 'Login gagal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
