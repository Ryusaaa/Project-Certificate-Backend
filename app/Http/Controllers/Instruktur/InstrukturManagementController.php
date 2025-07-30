<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instruktur;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;

class InstrukturManagementController extends Controller
{
    public function index()
    {
        $instrukturs = Instruktur::all();

        if ($instrukturs->isEmpty()) {
            return response()->json(['message' => 'No instructors found'], 404);
        }

        return response()->json($instrukturs, 200);
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
