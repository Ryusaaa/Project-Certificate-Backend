<?php

namespace App\Http\Controllers\DataActivity;

use App\Http\Controllers\Controller;
use App\Models\DataActivityType;
use Illuminate\Http\Request;

class DataActivityTypeController extends Controller
{
    /**
     * Display a listing of the resource with sort and pagination.
     */
    public function index(Request $request)
    {
        $query = DataActivityType::query();

        $sortKey = $request->input('sortKey', 'type_name');
        $sortOrder = $request->input('sortOrder', 'asc');
        
        $query->orderBy($sortKey, $sortOrder);

        $perPage = $request->input('perPage', 10);
        $data = $query->paginate($perPage);
        return response()->json([
            'total' => $data->total(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'per_page' => $data->perPage(),
            'message' => 'Data activity types retrieved successfully.',
            'data' => $data->items(),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'type_name' => 'required|string|unique:data_activity_types,type_name|max:255',
        ]);
        
        $data = DataActivityType::create($request->all());
        
        return response([
            'data' => $data,
            'message' => 'Data activity type created successfully.'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = DataActivityType::find($id);
        if (!$data) {
            return response([
                'message' => 'Data activity type not found.'
            ], 404);
        }
        return response([
            'data' => $data,
            'message' => 'Data activity type retrieved successfully.'
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = DataActivityType::find($id);
        if (!$data) {
            return response([
                'message' => 'Data activity type not found.'
            ], 404);
        }
        
        $request->validate([
            'type_name' => 'sometimes|required|string|unique:data_activity_types,type_name,'.$id.'|max:255',
        ]);

        $data->update($request->all());
        
        return response([
            'data' => $data,
            'message' => 'Data activity type updated successfully.'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = DataActivityType::find($id);
        if (!$data) {
            return response([
                'message' => 'Data activity type not found.'
            ], 404);
        }
        
        if ($data->activities()->exists()) {
            return response([
                'message' => 'Cannot delete this activity type because it is being used by one or more activities.'
            ], 409); // 409 Conflict
        }

        $data->delete();
        
        return response([
            'message' => 'Data activity type deleted successfully.'
        ], 200);
    }
}