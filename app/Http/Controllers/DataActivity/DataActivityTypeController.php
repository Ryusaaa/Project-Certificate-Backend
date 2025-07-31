<?php

namespace App\Http\Controllers\DataActivityType;


use App\Http\Controllers\Controller;
use App\Models\DataActivityType;
use Illuminate\Http\Request;

class DataActivityTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = DataActivityType::all();
        return response([
            'data' => $data,
            'message' => 'Data activity types retrieved successfully.'
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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
        $data->delete();
        return response([
            'message' => 'Data activity type deleted successfully.'
        ], 200);
    }
}