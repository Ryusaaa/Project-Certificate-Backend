<?php

namespace App\Http\Controllers;

use App\Models\DataActivity;
use Illuminate\Http\Request;

class DataActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = DataActivity::with('activityType')->get();
        return response([
            'data' => $data,
            'message' => 'Data activities Founded.'
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'activity_name' => 'required|string|max:255',
            'activity_type_id' => 'required|exists:data_activity_types,id',
            'description' => 'nullable|string',
        ]);

        $dataActivity = DataActivity::create($request->all());

        return response([
            'data' => $dataActivity,
            'message' => 'Data activity created successfully.'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $dataActivity = DataActivity::with('activityType')->find($id);

        if (!$dataActivity) {
            return response([
                'message' => 'Data activity not found.'
            ], 404);
        }

        return response([
            'data' => $dataActivity
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $dataActivity = DataActivity::find($id);

        if (!$dataActivity) {
            return response([
                'message' => 'Data activity not found.'
            ], 404);
        }

        $request->validate([
            'activity_name' => 'required|string|max:255',
            'activity_type_id' => 'required|exists:data_activity_types,id',
            'description' => 'nullable|string',
        ]);

        $dataActivity->update($request->all());

        return response([
            'data' => $dataActivity,
            'message' => 'Data activity updated successfully.'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $dataActivity = DataActivity::find($id);

        if (!$dataActivity) {
            return response([
                'message' => 'Data activity not found.'
            ], 404);
        }

        $dataActivity->delete();

        return response([
            'message' => 'Data activity deleted successfully.'
        ], 200);
    }
}
