<?php

namespace App\Http\Controllers\DataActivity;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\DataActivity;
use App\Models\Instruktur;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Sertifikat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DataActivityController extends Controller
{
    /**
     * Helper function to handle Base64 encoded images embedded in a string.
     * It saves them as files and replaces the src attribute with the new URL.
     */


    public function updateSertifikatTemplate(Request $request, $id)
    {
        try {
            $request->validate([
                'sertifikat_template_id' => 'required|exists:sertifikats,id',
            ]);

            $dataActivity = DataActivity::findOrFail($id);
            $dataActivity->sertifikat_id = $request->sertifikat_template_id;
            $dataActivity->save();

            // Tambahkan data sertifikat ke response
            $dataActivity->load('sertifikat');  // Eager load relasi sertifikat

            return response()->json([
                'message' => 'Template sertifikat berhasil disimpan',
                'data' => $dataActivity
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal menyimpan template sertifikat',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCertificateTemplates()
    {
    $templates = Sertifikat::select('id', 'name', 'is_active')
                            ->where('is_active', true)
                            ->get();
    return response()->json([
        'data' => $templates,
        'message' => 'Template sertifikat berhasil diambil'
    ]);


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
                'merchant_id' => $item->merchant_id,
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
            'merchant_id' => 'required|exists:merchants,id',
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
            'merchant_id' => $request->merchant_id,
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
        $dataActivity = DataActivity::with(['activityType', 'instruktur', 'peserta', 'sertifikat'])
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
            'total_peserta' => $dataActivity->peserta->count(),
            'sertifikat_id' => $dataActivity->sertifikat_id,
            'sertifikat' => $dataActivity->sertifikat,
            'merchant_id' => $dataActivity->merchant_id,
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
            'merchant_id' => 'sometimes|required|exists:merchants,id',
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

    public function attachTemplates(Request $request, $activityId)
    {
        try {
            $validated = $request->validate([
                'sertifikat_ids' => 'required|array',
                'sertifikat_ids.*' => 'exists:sertifikats,id'
            ]);

            $dataActivity = DataActivity::findOrFail($activityId);

            // Validasi merchant_id pada dataActivity
            if (!$dataActivity->merchant_id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'merchant_id tidak ditemukan pada data activity.'
                ], 400);
            }

            $alreadySent = [];
            foreach ($validated['sertifikat_ids'] as $templateId) {
                $existing = $dataActivity->sertifikat()
                    ->wherePivot('sertifikat_id', $templateId)
                    ->wherePivot('status', '!=', 'approved')
                    ->first();

                if ($existing) {
                    $alreadySent[] = $templateId;
                    continue;
                }

                $dataActivity->sertifikat()->attach($templateId, [
                    'status' => 'pending',
                    'is_active' => false
                ]);

                // Jika merchant_id = 1, attach juga ke instruktur dengan merchant_id = 1
                if ($dataActivity->merchant_id == 1) {
                    $instruktur = \App\Models\Instruktur::find($dataActivity->instruktur_id);
                    if ($instruktur && $instruktur->merchant_id == 1) {
                        // Di sini bisa tambahkan logika khusus jika perlu
                        // Misal: update kolom pivot lain, atau log, dsb.
                    }
                }
            }

            if (!empty($alreadySent)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Beberapa template sudah pernah dikirim dan belum di-approve.',
                    'already_sent' => $alreadySent
                ], 409);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Templates attached successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error attaching templates: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan semua template yang terpasang pada data activity
     */
    public function listTemplates($activityId)
    {
        try {
            $dataActivity = DataActivity::findOrFail($activityId);
            
            $templates = $dataActivity->sertifikat()
                ->with(['downloads']) // Include any needed relations
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'background_image' => $template->background_image,
                        'status' => $template->pivot->status,
                        'is_active' => $template->pivot->is_active,
                        'preview_url' => $template->background_image, // Adjust based on your preview logic
                        'created_at' => $template->pivot->created_at
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $templates
            ]);

        } catch (\Exception $e) {
            Log::error('Error listing templates: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Approve template and reject others
     */
    public function approveTemplate(Request $request, $activityId)
    {
        try {
            $validated = $request->validate([
                'sertifikat_id' => 'required|exists:sertifikats,id'
            ]);

            DB::beginTransaction();

            $dataActivity = DataActivity::findOrFail($activityId);
            
            // Set all templates to rejected first
            $dataActivity->sertifikat()->updateExistingPivot(
                $dataActivity->sertifikat()->pluck('sertifikats.id'),
                ['status' => 'rejected', 'is_active' => false]
            );

            // Set the chosen template as approved and active
            $dataActivity->sertifikat()->updateExistingPivot(
                $validated['sertifikat_id'],
                ['status' => 'approved', 'is_active' => true]
            );

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Template approved successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error approving template: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending templates for instructor approval
     */
    public function getPendingTemplates($activityId)
    {
        try {
            $dataActivity = DataActivity::findOrFail($activityId);
            
            $templates = $dataActivity->sertifikat()
                ->wherePivot('status', 'pending')
                ->get()
                ->map(function ($template) {
                    return [
                        'id' => $template->id,
                        'name' => $template->name,
                        'background_image' => $template->background_image,
                        'preview_url' => $template->background_image, // Adjust based on your preview logic
                        'created_at' => $template->pivot->created_at
                    ];
                });

            return response()->json([
                'status' => 'success',
                'data' => $templates
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting pending templates: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
