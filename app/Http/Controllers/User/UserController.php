<?php

namespace App\Http\Controllers\User;



use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function downloadTemplate() {
        $filePath = public_path('template/template_peserta.xlsx');
        if (!file_exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template file not found.'
            ], 404);
        }
        return response()->download($filePath, 'template_peserta.xlsx');
    }



        public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_excel' => 'required|mimes:xlsx,xls|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $file = $request->file('file_excel');

            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getActiveSheet();
            $data = $sheet->toArray();

            DB::beginTransaction();
            $importedCount = 0;

            foreach (array_slice($data, 1) as $row) {
                if (array_filter($row)) {
                    $pesertaData = [
                        'name' => $row[0],
                        'email' => $row[1],
                        'no_hp' => $row[2],
                        'asal_institusi' => $row[3],
                        'password' => Hash::make($row[4]),
                        'role_id' => 3
                    ];

                    $existingPeserta = User::where('email', $pesertaData['email'])->first();

                    if (!$existingPeserta) {
                        User::create($pesertaData);
                        $importedCount++;
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data peserta berhasil diunggah dan disimpan.',
                'imported_count' => $importedCount
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengunggah atau memproses file.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $query = User::with('role');

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
            $query->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                  ->orderBy('roles.name', $sortOrder)
                  ->select('users.*');
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
            ];
        });

        return response()->json([
            'total' => $items->total(),
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
            'per_page' => $items->perPage(),
            'message' => 'User list fetched successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'no_hp' => 'required|string|max:15',
            'asal_institusi' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id', 
        ]);

        $validated['password'] = bcrypt($validated['password']);
        $user = User::create($validated);

        return response([
            'message' => 'User created successfully.',
            'data' => $user
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = User::find($id);
        if (is_null($data)) {
            return response([
                'message' => 'User not found.'
            ], 404);
        }

        return response([
            'message' => 'User founded!',
            'data' => $data
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);
        if (is_null($user)) {
            return response([
                'message' => 'User not found.'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$id,
            'no_hp' => 'required|string|max:15',
            'asal_institusi' => 'required|string|max:255',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = bcrypt($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response([
            'message' => 'User updated successfully.',
            'data' => $user
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = User::find($id);
        if (is_null($data)) {
            return response([
                'message' => 'User not found.'
            ], 404);
        }

        $data->delete();
        return response([
            'message' => 'User deleted successfully!',
            'data' => $data
        ], 200);
    }

   
}
