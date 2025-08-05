<?php

namespace App\Http\Controllers\DataActivity;

use App\Http\Controllers\Controller;
use App\Models\DataActivity;
use Illuminate\Http\Request;
use App\Models\Instruktur;
use App\Models\User;

class DataActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
    
    
    $query = DataActivity::with('activityType', 'instruktur');

    if ($search = $request->input('search')) {
        $searchLower = strtolower($search);
        $query->where(function ($q) use ($searchLower) {
            $q->where(function($q) use ($searchLower) {
                $q->whereRaw("LOWER(activity_name) like ?", ["%{$searchLower}%"]);
            })
            ->orWhere(function($q) use ($searchLower) {
                $q->whereRaw("LOWER(description) like ?", ["%{$searchLower}%"]);
            })
            ->orWhereHas('instruktur', function ($q2) use ($searchLower) {
                $q2->whereRaw("LOWER(name) like ?", ["%{$searchLower}%"]);
            })
            ->orWhereHas('activityType', function ($q3) use ($searchLower) {
                $q3->whereRaw("LOWER(type_name) like ?", ["%{$searchLower}%"]);
            });
        });
    }

    // SORT (default: by activity_name asc)
    $sortKey = $request->input('sortKey', 'activity_name');
    $sortOrder = $request->input('sortOrder', 'asc');
    
    // Validasi minimum pagination
    $perPage = max(5, $request->input('perPage', 10));

    // Sorting berdasarkan kolom relasi dan length
    if ($sortKey === 'activity_type_name') {
        $query->leftJoin('data_activity_types', 'data_activities.activity_type_id', '=', 'data_activity_types.id')
              ->orderBy('data_activity_types.type_name', $sortOrder)
              ->select('data_activities.*');
    } 
    elseif ($sortKey === 'instruktur_name') {
        $query->leftJoin('instrukturs', 'data_activities.instruktur_id', '=', 'instrukturs.id')
              ->orderBy('instrukturs.name', $sortOrder)
              ->select('data_activities.*');
    }
    elseif ($sortKey === 'description_length') {
        $query->orderByRaw('LENGTH(COALESCE(description, \'\')) ' . $sortOrder);
    }
    else {
        $query->orderBy($sortKey, $sortOrder);
    }

    // PAGINATION
    $activities = $query->paginate($perPage);

    // Format response
    $result = $activities->getCollection()->map(function ($item) {
        return [
            'id' => $item->id,
            'activity_name' => $item->activity_name,
            'date' => $item->date,
            'activity_type_id' => $item->activity_type_id,
            'activity_type_name' => $item->activityType->type_name ?? null,
            'description' => $item->description,
            'instruktur_id' => $item->instruktur_id,
            'instruktur_name' => $item->instruktur->name ?? null,
        ];
    });

    return response()->json([
        'total' => $activities->total(),
        'current_page' => $activities->currentPage(),
        'last_page' => $activities->lastPage(),
        'per_page' => $activities->perPage(),
        'message' => 'Data activities fetched successfully.',
        'data' => $result,
    ]);
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
