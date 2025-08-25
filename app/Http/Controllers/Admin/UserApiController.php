<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Admin::with('role');

        // SEARCH
        if ($search = $request->input('search')) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw("LOWER(name) like ?", ["%{$searchLower}%"])
                  ->orWhereRaw("LOWER(email) like ?", ["%{$searchLower}%"])
                  ->orWhereHas('role', function ($q2) use ($searchLower) {
                      $q2->whereRaw("LOWER(name) like ?", ["%{$searchLower}%"]);
                  });
            });
        }

        // SORT
        $sortKey = $request->input('sortKey', 'name');
        $sortOrder = $request->input('sortOrder', 'asc');
        if ($sortKey === 'role_name') {
            $query->leftJoin('roles', 'admins.role_id', '=', 'roles.id')
                  ->orderBy('roles.name', $sortOrder)
                  ->select('admins.*');
        } else {
            $query->orderBy($sortKey, $sortOrder);
        }

        // PAGINATION
        $perPage = max(5, $request->input('perPage', 10));
        $items = $query->paginate($perPage);

        // Format response
        $result = $items->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'role_id' => $item->role_id,
                'role_name' => $item->role->name ?? null,
                'merchant_id' => $item->merchant_id,
            ];
        });

        return response()->json([
            'total' => $items->total(),
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
            'per_page' => $items->perPage(),
            'message' => 'Admin list fetched successfully.',
            'data' => $result,
        ]);
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
                'merchant_id' => 'required|integer'
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Validation failed', 'error' => $e->getMessage()], 422);
        }

        $merchant = Merchant::find($request->merchant_id);
        if (!$merchant) {
            $merchant = Merchant::create([
                'id' => $request->merchant_id,
                'name' => 'Merchant ' . $request->merchant_id,
            ]);
        }
        

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => '1',
            'merchant_id' => $merchant->id,
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
