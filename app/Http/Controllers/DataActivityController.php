<?php

namespace App\Http\Controllers;

use App\Models\DataActivity;
use Illuminate\Http\Request;
use App\Models\Instruktur;

class DataActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = DataActivity::with('activityType', 'instruktur')->get();

        $result = $data->map(function ($item) {
        return [
            'id' => $item->id,
            'activity_name' => $item->activity_name,
            'date' => $item->date,
            'activity_type_id' => $item->activity_type_id,
            'activity_type_name' => $item->activityType ? $item->activityType->type_name : null,
            'description' => $item->description,
            'instruktur_id' => $item->instruktur_id,
            'instruktur_name' => $item->instruktur ? $item->instruktur->name : null,
        ];
    });

        return response([
            'data' => $result,
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
            'date' => 'required|date|after_or_equal:today',
            'activity_type_id' => 'required|exists:data_activity_types,id',
            'description' => 'nullable|string',
            'instruktur_id' => 'required|exists:instrukturs,id',
        ]);

        $instruktur = Instruktur::where('id', $request->instruktur_id)->first();

        if (!$instruktur) {
            return response([
                'message' => 'Instruktur not found.'
            ], 404);
        }

        $dataActivity = DataActivity::create([
            'activity_name' => $request->activity_name,
            'date' => $request->date,
            'activity_type_id' => $request->activity_type_id,
            'description' => $request->description,
            'instruktur_id' => $instruktur->id,
        ]);

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
            'date' => 'required|date',
            'activity_type_id' => 'required|exists:data_activity_types,id',
            'description' => 'nullable|string',
            'instruktur_id' => 'required|exists:instrukturs,id', 
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
