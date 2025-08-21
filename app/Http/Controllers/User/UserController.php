<?php

namespace App\Http\Controllers\User;



use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Http\Controllers\Controller;
use App\Models\DataActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Throwable;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    // Format nomor HP untuk menyimpan ke database

    public function index(Request $request)
    {
        try {
            $query = User::with(['role', 'daftarActivity', 'merchant']);

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
                // [FIX] Menambahkan groupBy untuk memastikan paginasi berjalan stabil saat menggunakan join
                $query->leftJoin('roles', 'users.role_id', '=', 'roles.id')
                    ->orderBy('roles.name', $sortOrder)
                    ->select('users.*')
                    ->groupBy('users.id');
            } else {
                // [FIX] Whitelist kolom yang diizinkan untuk sorting untuk keamanan
                $allowedSortKeys = ['id', 'name', 'email', 'asal_institusi', 'created_at'];
                if (in_array($sortKey, $allowedSortKeys)) {
                    $query->orderBy($sortKey, $sortOrder);
                }
            }

            // PAGINATION
            $perPage = max(5, $request->input('perPage', 10));
            $items = $query->paginate($perPage);

            // Format response
            $result = $items->getCollection()->map(function ($item) {
                try {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'no_hp' => $item->no_hp,
                        'asal_institusi' => $item->asal_institusi,
                        'email' => $item->email,
                        'role_id' => $item->role_id,
                        // [FIX] Pemeriksaan null yang lebih aman untuk menghindari error jika relasi role tidak ada
                        'role_name' => $item->role ? $item->role->name : null,
                        'merchant_id' => $item->merchant_id,
                        'merchant' => $item->merchant,
                        'activities' => $item->daftarActivity ? $item->daftarActivity->map(function ($activity) {
                            return [
                                'id' => $activity->id,
                                'activity_name' => $activity->activity_name
                            ];
                        })->toArray() : []
                    ];
                } catch (\Exception $e) {
                    Log::error('Error formatting user data: ' . $e->getMessage() . ' for user ID: ' . $item->id);
                    return null;
                }
            })->filter();

            return response()->json([
                'success' => true,
                'total' => $items->total(),
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'message' => 'User list fetched successfully.',
                'data' => array_values($result->toArray()),
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching users: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data pengguna.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

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
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'no_hp' => [
                    'required',
                    'string',
                    'max:15',
                    'regex:/^(62|08)[0-9]{7,13}$/'
                ],
                'asal_institusi' => 'required|string|max:255',
                'password' => 'nullable|string|min:8|',
                'role_id' => 'required|exists:roles,id',
            ], [
                'email.unique' => 'Email sudah terdaftar',
                // 'password.required' => 'Password harus diisi',
                // 'password.min' => 'Password minimal 8 karakter',
                // 'password.confirmed' => 'Konfirmasi password tidak sesuai',
                'no_hp.regex' => 'Nomor HP harus diawali 62 atau 08 dan terdiri dari 8-15 digit angka',
                'no_hp.required' => 'Nomor HP harus diisi',
                'asal_institusi.required' => 'Asal institusi harus diisi',
                'name.required' => 'Nama harus diisi',
                'email.required' => 'Email harus diisi',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // $validated = $validator->validated();
            // $validated['password'] = bcrypt($validated['password']);
            
            $loggedInUser = auth('sanctum')->user();
            $validated['merchant_id'] = $loggedInUser ? $loggedInUser->merchant_id : 1;
            
            $user = User::create($validated);

            return response([
                'message' => 'User created successfully.',
                'data' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat membuat user.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // Download template Excel untuk import peserta
    public function downloadTemplate()
    {
        $filePath = public_path('template/template_peserta.xlsx');
        if (!file_exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template file not found.'
            ], 404);
        }
        return response()->download($filePath, 'template_peserta.xlsx');
    }


    // Import Users dari file Excel
    public function import(Request $request, $id)
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
            $rowNumber = 1;

            $loggedInUser = auth('sanctum')->user();
            $merchant_id = $loggedInUser ? $loggedInUser->merchant_id : 1;

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

                if (empty($row[0])) {
                    $errors[] = "Baris {$rowNumber}: Nama tidak boleh kosong.";
                    continue;
                }

                if (!preg_match('/^(62|08)[0-9]{7,13}$/', $row[2])) {
                    $errors[] = "Baris {$rowNumber}: Nomor HP '{$row[2]}' harus diawali 62 atau 08 dan terdiri dari 8-15 digit angka.";
                    continue;
                }

                $existingUser = User::where('email', $email)->first();

                if ($existingUser) {
                    if (!$existingUser->daftarActivity()->where('data_activity_id', $id)->exists()) {
                        $existingUser->daftarActivity()->attach($id);
                        $importedCount++;
                    }
                } else {
                    $newUserData = [
                        'name'           => $row[0],
                        'email'          => $email,
                        'no_hp'          => $nomorHpFormatted,
                        'asal_institusi' => $row[3] ?? null,
                        'password'       => Hash::make('password'),
                        'role_id'        => 3,
                        'merchant_id'    => $merchant_id,
                    ];
                    
                    $newUser = User::create($newUserData);
                    $newUser->daftarActivity()->attach($id);
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
                'message' => "Berhasil menambahkan {$importedCount} peserta ke kegiatan.",
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
    /**
     * Display the specified resource.
     */
    public function inputUserDataActivity(Request $request, $id)
    {
        $dataActivity = DataActivity::findOrFail($id);
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

        $dataActivity->peserta()->attach($user->id);

        return response()->json([
            'message' => 'User successfully added to data activity.',
            'data' => [$dataActivity, $user]
        ], 200);
    }

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
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
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
