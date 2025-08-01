<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instruktur;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class InstrukturManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Instruktur::query();

        // SEARCH - Case insensitive search on instructor name and email
        if ($search = $request->input('search')) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw("LOWER(name) like ?", ["%{$searchLower}%"])
                  ->orWhereRaw("LOWER(email) like ?", ["%{$searchLower}%"]);
            });
        }

        // SORT (default: by name asc)
        $sortKey = $request->input('sortKey', 'name');
        $sortOrder = $request->input('sortOrder', 'asc');
        $query->orderBy($sortKey, $sortOrder);

        // PAGINATION
        $perPage = $request->input('perPage', 10);
        $instrukturs = $query->paginate($perPage);

        // Format response
        $result = $instrukturs->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'role_id' => $item->role_id,
            ];
        });

        return response()->json([
            'total' => $instrukturs->total(),
            'current_page' => $instrukturs->currentPage(),
            'last_page' => $instrukturs->lastPage(),
            'per_page' => $instrukturs->perPage(),
            'message' => 'Data instructors fetched successfully.',
            'data' => $result,
        ]);
    }

    public function show($id)
    {
        $instruktur = Instruktur::findOrFail($id);

        if (!$instruktur) {
            return response()->json(['message' => 'Instruktur not found'], 404);
        }

        return response()->json($instruktur, 200);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:instrukturs,email',
                'password' => 'required|string|min:6'
            ]);
        

        $instruktur = Instruktur::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => '2'
        ]);

        return response()->json($instruktur, 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the instructor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
        $instruktur = Instruktur::findOrFail($id);
        
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:instrukturs,email,' . $instruktur->id,
            'password' => 'sometimes|required|string|min:6'
        ]);

        $instruktur->update($request->only(['name', 'email', 'password']));

        return response()->json($instruktur, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the instructor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $instruktur = Instruktur::findOrFail($id);

        if (!$instruktur) {
            return response()->json(['message' => 'Instruktur not found'], 404);
        }

        $instruktur->delete();

        return response()->json(['message' => 'Instruktur deleted successfully'], 200);
    }
}
