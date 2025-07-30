<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $admins = Admin::all();
        
        if ($admins->isEmpty()) {
            return response()->json([
                'message' => 'Belum ada data admin',
                'data' => []
            ], 200);
        }

        return response()->json([
            'message' => 'Data admin berhasil diambil',
            'data' => $admins
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:admins,email',
                'password' => 'required|string|min:8',
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Validation failed', 'error' => $e->getMessage()], 422);
        }

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => '1'
        ]);

        return response()->json([
            'message' => 'Admin created successfully',
            'admin' => $admin,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Admin $admin)
    {
        //
        return response()->json($admin);
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Admin $admin)
    {
        //
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => [
                    'required',
                    'email',
                    ],
                'password' => 'nullable|string|min:8',
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Validation failed', 'error' => $e->getMessage()], 422);
        }
        if (Admin::where('email', $request->email)->where('id', '!=', $admin->id)->exists()) {
            return response()->json(['message' => 'Email already exists'], 422);
        }
        $admin->name = $request->name;
        $admin->email = $request->email;
        if ($request->has('password')) {
            $admin->password = Hash::make($request->password);
        }

        $admin->save();
        return response()->json([
            'message' => 'Admin updated successfully',
            'admin' => $admin,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Admin $admin)
    {
        //
        $admin->delete();
        return response()->json([
            'message' => 'Admin deleted successfully',
        ], 200);
    }
}
