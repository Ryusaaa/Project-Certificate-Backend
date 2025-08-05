<?php

namespace App\Http\Controllers\User;



use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */

     private function formatNomorHP($nomor)
    {
        if (empty($nomor)) {
            return null;
        }
        $nomor = preg_replace('/[^0-9]/', '', $nomor);
        if (substr($nomor, 0, 1) == '0') {
            return '62' . substr($nomor, 1);
        }
        if (substr($nomor, 0, 2) == '62') {
            return $nomor;
        }
        return $nomor;
    }

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
            $errors = [];
            $rowNumber = 2; 

            foreach (array_slice($data, 1) as $row) {
                $rowNumber++; 
                if (!array_filter($row)) {
                    continue;
                }

                $email = filter_var($row[1], FILTER_VALIDATE_EMAIL);
                if (!$email) {
                    $errors[] = "Baris {$rowNumber}: Format email '{$row[1]}' tidak valid.";
                    continue; 
                }
                
                $nomorHpFormatted = $this->formatNomorHP($row[2]);

                $pesertaData = [
                    'name'           => $row[0] ?? null,
                    'email'          => $email,
                    'no_hp'          => $nomorHpFormatted, 
                    'asal_institusi' => $row[3] ?? null,
                    'password'       => Hash::make($row[4] ?? 'password'), 
                    'role_id'        => 3
                ];

                if (empty($pesertaData['name']) || empty($row[4])) {
                    $errors[] = "Baris {$rowNumber}: Nama atau password tidak boleh kosong.";
                    continue;
                }

                if (!preg_match('/^(62|08)[0-9]{7,13}$/', $row[2])) {
                    $errors[] = "Baris {$rowNumber}: Nomor HP '{$row[2]}' harus diawali 62 atau 08 dan terdiri dari 8-15 digit angka.";
                    continue;
                }

                $existingPeserta = User::where('email', $pesertaData['email'])->first();
                if (!$existingPeserta) {
                    User::create($pesertaData);
                    $importedCount++;
                }
            }

            if (!empty($errors)) {
                DB::rollBack(); 
                return response()->json([
                    'status' => 'error',
                    'message' => 'Beberapa data gagal diimpor.',
                    'errors' => $errors
                ], 422);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => "Data berhasil diunggah. {$importedCount} data baru disimpan.",
            ], 200);

        } catch (Throwable $e) { 
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan internal saat memproses file.',
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
                'no_hp' => $item->no_hp,
                'asal_institusi' => $item->asal_institusi,
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
            'no_hp' => [
                'required',
                'string',
                'max:15',
                'regex:/^(62|08)[0-9]{7,13}$/'
            ],
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
            'no_hp' => [
                'required',
                'string',
                'max:15',
                'regex:/^(62|08)[0-9]{7,13}$/'
            ],
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
