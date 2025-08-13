<?php

namespace App\Http\Controllers\Sertifikat;

use App\Http\Controllers\Controller;
use App\Models\Sertifikat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\AutoEncoder;

class SertifikatTemplateController extends Controller
{
    /**
     * Lebar PDF dalam satuan points untuk A4 Landscape.
     * @var int
     */
    private $pdfWidth = 842;

    /**
     * Tinggi PDF dalam satuan points untuk A4 Landscape.
     * @var int
     */
    private $pdfHeight = 595;

    /**
     * Mengunggah dan memproses gambar (background atau element).
     */
    public function uploadImage(Request $request)
    {
        try {
            $inputName = null;
            if ($request->hasFile('background_image')) {
                $inputName = 'background_image';
            } elseif ($request->hasFile('element_image')) {
                $inputName = 'element_image';
            }

            if (!$inputName) {
                return response()->json(['status' => 'error', 'message' => 'No image file found.'], 400);
            }

            $request->validate([
                $inputName => 'required|image|mimes:jpeg,png,jpg|max:10240',
            ]);

            $file = $request->file($inputName);
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = 'certificates/' . $filename;

            $manager = new ImageManager(new Driver());
            $image = $manager->read($file->getRealPath())
                ->scaleDown(width: 2000)
                ->encode(new AutoEncoder(quality: 90));

            // Simpan gambar yang sudah diproses
            Storage::disk('public')->put($path, (string) $image);
            $url = Storage::url($path);

            Log::info('Image uploaded successfully', ['path' => $path, 'url' => $url]);

            return response()->json([
                'status' => 'success',
                'url' => $url,
                'message' => 'Image uploaded successfully'
            ]);

        } catch (ValidationException $e) {
            Log::error('Image upload validation failed', ['errors' => $e->errors()]);
            return response()->json(['status' => 'error', 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error uploading image', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['status' => 'error', 'message' => 'Could not upload image: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan semua template sertifikat.
     */
    public function index()
    {
        $templates = Sertifikat::all();
        return response()->json(['status' => 'success', 'data' => $templates]);
    }

    /**
     * Menyimpan template sertifikat baru.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'background_image' => 'required|string',
                'elements' => 'required|array',
                'certificate_number_format' => 'nullable|string'
            ]);

            // Ubah URL gambar menjadi path penyimpanan
            $background_path = str_replace(Storage::url(''), '', $validated['background_image']);
            if (!Storage::disk('public')->exists($background_path)) {
                 return response()->json(['status' => 'error', 'message' => 'Background image not found on storage.'], 404);
            }

            $template = Sertifikat::create([
                'name' => $validated['name'],
                'background_image' => $background_path,
                'elements' => $this->processElements($validated['elements']),
                'layout' => [
                    'width' => $this->pdfWidth,
                    'height' => $this->pdfHeight,
                    'orientation' => 'landscape'
                ]
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Template created successfully',
                'data' => $template,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating template', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Failed to create template: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan detail satu template.
     */
    public function show($id)
    {
        try {
            $template = Sertifikat::findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $template]);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Template not found'], 404);
        }
    }

    /**
     * Memperbarui template yang ada.
     */
    public function update(Request $request, $id)
    {
        try {
            $template = Sertifikat::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'background_image' => 'sometimes|required|string',
                'elements' => 'sometimes|required|array',
                'certificate_number_format' => 'sometimes|nullable|string'
            ]);

            if (isset($validated['background_image'])) {
                $validated['background_image'] = str_replace(Storage::url(''), '', $validated['background_image']);
            }
            if (isset($validated['elements'])) {
                 $validated['elements'] = $this->processElements($validated['elements']);
            }

            // Validate certificate number format if provided
            if (isset($validated['certificate_number_format'])) {
                if ($validated['certificate_number_format'] !== null && 
                    !preg_match('/X+/', $validated['certificate_number_format'])) {
                    throw new \Exception('Format nomor sertifikat harus mengandung minimal satu X sebagai placeholder nomor');
                }
            }

            $template->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Template updated successfully',
                'data' => $template
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating template', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Failed to update template: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Menghapus template.
     */
    public function destroy($id)
    {
        try {
            $template = Sertifikat::findOrFail($id);
            Storage::disk('public')->delete($template->background_image);
            $template->delete();

            return response()->json(['status' => 'success', 'message' => 'Template deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Template not found'], 404);
        }
    }

    /**
     * Memproses dan membersihkan data elemen dari request.
     */
    private function processElements(array $elements): array
    {
        // Fungsi ini sudah cukup baik, tidak perlu perubahan signifikan.
        // Pastikan data yang dikirim dari frontend sudah memiliki struktur yang benar.
        return array_map(function($element) {
            // ... (logika dari kode asli Anda bisa ditaruh di sini)
            // Sanitasi dasar untuk keamanan
            $element['x'] = (float) $element['x'];
            $element['y'] = (float) $element['y'];
            if (isset($element['width'])) {
                 $element['width'] = (float) $element['width'];
            }
            if (isset($element['height'])) {
                 $element['height'] = (float) $element['height'];
            }
            if (isset($element['fontSize'])) {
                 $element['fontSize'] = (int) $element['fontSize'];
            }
            return $element;
        }, $elements);
    }
}