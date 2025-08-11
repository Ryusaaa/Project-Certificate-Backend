<?php

namespace App\Http\Controllers\DataActivity;

use App\Http\Controllers\Controller;
use App\Models\DataActivity;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Sertifikat;

class DataActivityController extends Controller
{
    /**
     * Helper function to handle Base64 encoded images embedded in a string.
     * It saves them as files and replaces the src attribute with the new URL.
     */


    public function getCertificateTemplates()
    {
        $templates = Sertifikat::where('is_active', true)->get();
        return response()->json($templates);
    }


    private function handleEmbeddedImages($description)
    {
        $pattern = '/<img[^>]+src="data:image\/([^;]+);base64,([^"]+)"[^>]*>/i';
        return preg_replace_callback($pattern, function ($matches) {
            $ext = $matches[1];
            $base64 = $matches[2];
            $imageData = base64_decode($base64);
            $filename = 'activity_images/' . uniqid() . '.' . $ext;
            file_put_contents(public_path('storage/' . $filename), $imageData);
            $url = asset('storage/' . $filename);
            return '<img src="' . $url . '" />';
        }, $description);
    }

    /**
     * Display a listing of the resource with search, sort, and pagination.
     */
    public function index(Request $request)
    {
        $query = DataActivity::with('activityType', 'instruktur');

        // SEARCH
        if ($search = $request->input('search')) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw("LOWER(activity_name) like ?", ["%{$searchLower}%"])
                    ->orWhereRaw("LOWER(description) like ?", ["%{$searchLower}%"])
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
        $perPage = max(5, $request->input('perPage', 10));

        if ($sortKey === 'activity_type_name') {
            $query->join('data_activity_types', 'data_activities.activity_type_id', '=', 'data_activity_types.id')
                ->orderBy('data_activity_types.type_name', $sortOrder)
                ->select('data_activities.*');
        } elseif ($sortKey === 'instruktur_name') {
            $query->join('instrukturs', 'data_activities.instruktur_id', '=', 'instrukturs.id')
                ->orderBy('instrukturs.name', $sortOrder)
                ->select('data_activities.*');
        } elseif ($sortKey === 'description_length') {
            $query->orderByRaw('LENGTH(COALESCE(description, \'\')) ' . $sortOrder);
        } else {
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
                'time_start' => $item->time_start,
                'time_end' => $item->time_end,
                'activity_type_id' => $item->activity_type_id,
                'activity_type_name' => $item->activityType->type_name ?? null,
                'description' => $item->description,
                'instruktur_id' => $item->instruktur_id,
                'instruktur_name' => $item->instruktur->name ?? null,
                'total_peserta' => $item->peserta->count(),
            ];
        });

        return response()->json([
            'total' => $activities->total(),
            'current_page' => $activities->currentPage(),
            'last_page' => $activities->lastPage(),
            'per_page' => $activities->perPage(),
            'message' => 'Data kegiatan berhasil diambil.',
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
            'time_start' => 'required|date_format:H:i',
            'time_end' => 'required|date_format:H:i|after:time_start',
            'activity_type_id' => 'required|exists:data_activity_types,id',
            'description' => 'nullable|string',
            'instruktur_id' => 'required|exists:instrukturs,id',
        ]);

        $description = $request->description ? $this->handleEmbeddedImages($request->description) : null;

        $dataActivity = DataActivity::create([
            'activity_name' => $request->activity_name,
            'date' => $request->date,
            'time_start' => $request->time_start,
            'time_end' => $request->time_end,
            'activity_type_id' => $request->activity_type_id,
            'description' => $description,
            'instruktur_id' => $request->instruktur_id,
        ]);

        return response()->json([
            'data' => $dataActivity,
            'message' => 'Data kegiatan berhasil dibuat.'
        ], 201);
    }

    /**
     * Display the specified resource along with the total participant count.
     */
    public function show(string $id)
    {
        $dataActivity = DataActivity::with(['activityType', 'instruktur', 'peserta'])
            ->find($id);

        if (!$dataActivity) {
            return response()->json(['message' => 'Data kegiatan tidak ditemukan.'], 404);
        }

        // Format respons agar lebih jelas
        $result = [
            'id' => $dataActivity->id,
            'activity_name' => $dataActivity->activity_name,
            'date' => $dataActivity->date,
            'time_start' => $dataActivity->time_start,
            'time_end' => $dataActivity->time_end,
            'activity_type_name' => $dataActivity->activityType->type_name ?? null,
            'description' => $dataActivity->description,
            'instruktur_name' => $dataActivity->instruktur->name ?? null,
            'peserta' => $dataActivity->peserta,
            'total_peserta' => $dataActivity->peserta->count()
        ];

        return response()->json([
            'data' => $result,
            'message' => 'Detail data kegiatan berhasil diambil.'
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $dataActivity = DataActivity::find($id);

        if (!$dataActivity) {
            return response()->json(['message' => 'Data kegiatan tidak ditemukan.'], 404);
        }

        $request->validate([
            'activity_name' => 'sometimes|required|string|max:255',
            'date' => 'sometimes|required|date',
            'time_start' => 'sometimes|required|date_format:H:i',
            'time_end' => 'sometimes|required|date_format:H:i|after:time_start',
            'activity_type_id' => 'sometimes|required|exists:data_activity_types,id',
            'description' => 'nullable|string',
            'instruktur_id' => 'sometimes|required|exists:instrukturs,id',
        ]);

        $payload = $request->all();

        if ($request->has('description')) {
            $payload['description'] = $request->description ? $this->handleEmbeddedImages($request->description) : null;
        }

        $dataActivity->update($payload);

        return response()->json([
            'data' => $dataActivity,
            'message' => 'Data kegiatan berhasil diperbarui.'
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $dataActivity = DataActivity::find($id);

        if (!$dataActivity) {
            return response()->json(['message' => 'Data kegiatan tidak ditemukan.'], 404);
        }

        $dataActivity->delete();

        return response()->json(['message' => 'Data kegiatan berhasil dihapus.'], 200);
    }
}
