<?php

namespace App\Http\Controllers\Sertifikat;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sertifikat;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Str;

class SertifikatTemplateController extends Controller
{
    private $pdfWidth = 842;    // A4 Landscape width
    private $pdfHeight = 595;   // A4 Landscape height

    public function uploadImage(Request $request)
    {
        try {
            Log::info('Starting background image upload', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            $request->validate([
                'background_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $file = $request->file('background_image');
            Log::info('File details', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('certificates', $filename, 'public');

            $url = '/storage/' . $path;
            Log::info('Background image uploaded successfully', [
                'path' => $path,
                'public_url' => $url
            ]);

            return response()->json([
                'status' => 'success',
                'url' => $url,
                'message' => 'Background image uploaded successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error uploading background image', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupload background: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        $templates = Sertifikat::all();
        return response()->json([
            'status' => 'success',
            'data' => $templates
        ]);
    }

    public function store(Request $request)
    {
        try {
            Log::info('Creating new certificate template');

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'background_image' => 'required|string',
                'elements' => 'required|array'
            ]);

            // Clean background image URL to path
            $background_image = $validated['background_image'];
            if (preg_match('#/storage/certificates/([^/]+)$#', $background_image, $matches)) {
                $background_image = 'certificates/' . $matches[1];
            }

            // Verify background exists
            if (!Storage::disk('public')->exists($background_image)) {
                throw new \Exception('Background image not found');
            }

            // Create template
            $template = new Sertifikat();
            $template->name = $validated['name'];
            $template->background_image = $background_image;
            $template->elements = $this->processElements($validated['elements']);
            $template->layout = [
                'width' => $this->pdfWidth,
                'height' => $this->pdfHeight,
                'orientation' => 'landscape'
            ];

            if (!$template->save()) {
                throw new \Exception('Failed to save template');
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Template created successfully',
                'data' => $template
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating template: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $template = Sertifikat::findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $template
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template not found'
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $template = Sertifikat::findOrFail($id);

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'background_image' => 'sometimes|required|string',
                'elements' => 'sometimes|required|array'
            ]);

            if (isset($validated['background_image'])) {
                // Clean background image URL to path
                $background_image = $validated['background_image'];
                if (preg_match('#/storage/certificates/([^/]+)$#', $background_image, $matches)) {
                    $background_image = 'certificates/' . $matches[1];
                }
                $validated['background_image'] = $background_image;
            }

            if (isset($validated['elements'])) {
                $validated['elements'] = $this->processElements($validated['elements']);
            }

            $template->update($validated);

            return response()->json([
                'status' => 'success',
                'message' => 'Template updated successfully',
                'data' => $template
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating template: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $template = Sertifikat::findOrFail($id);
            
            // Delete background image if exists
            if ($template->background_image) {
                Storage::disk('public')->delete($template->background_image);
            }

            $template->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Template deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template not found'
            ], 404);
        }
    }

    public function generatePDF(Request $request, $id)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'recipient_name' => 'required|string',
                'certificate_number' => 'required|string',
                'date' => 'required|date'
            ]);

            // Get template
            $template = Sertifikat::findOrFail($id);
            
            // Format date
            setlocale(LC_TIME, 'id_ID');
            Carbon::setLocale('id');
            $dateText = Carbon::parse($validated['date'])->translatedFormat('d F Y');

            // Process template elements
            $elements = $this->prepareElements($template->elements, [
                '{NAMA}' => $validated['recipient_name'],
                '{NOMOR}' => $validated['certificate_number'],
                '{TANGGAL}' => $dateText
            ]);

            // Prepare PDF data
            $data = [
                'template' => $template,
                'elements' => $elements,
                'background_image' => Storage::disk('public')->path($template->background_image),
                'pageWidth' => $this->pdfWidth,
                'pageHeight' => $this->pdfHeight
            ];

            // Generate PDF
            $pdf = PDF::loadView('sertifikat.template', $data)
                     ->setPaper([0, 0, $this->pdfWidth, $this->pdfHeight], 'landscape');

            // Generate filename
            $filename = sprintf(
                'sertifikat_%s_%s_%s.pdf',
                Str::slug($validated['recipient_name']),
                Str::slug($validated['certificate_number']),
                now()->format('Ymd_His')
            );

            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error generating PDF: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function processElements($elements)
    {
        return array_map(function($element) {
            // Log incoming element for debugging
            Log::info('Processing element:', $element);

            // Ensure coordinates are within bounds
            $element['x'] = max(0, min($element['x'], $this->pdfWidth));
            $element['y'] = max(0, min($element['y'], $this->pdfHeight));

            // Remove any scaling-related properties
            unset($element['originalX'], $element['originalY']);
            unset($element['originalWidth'], $element['originalHeight']);

            // Handle image elements
            if ($element['type'] === 'image') {
                // Try to get image URL from various possible fields
                $imageUrl = null;
                foreach (['imageUrl', 'url', 'image', 'src'] as $field) {
                    if (!empty($element[$field])) {
                        $imageUrl = $element[$field];
                        break;
                    }
                }
                
                if ($imageUrl) {
                    // Clean the URL to path if it's a storage URL
                    if (preg_match('#/storage/certificates/([^/]+)$#', $imageUrl, $matches)) {
                        $imagePath = 'certificates/' . $matches[1];
                        Log::info('Image path resolved:', ['path' => $imagePath]);
                        
                        // Verify image exists
                        if (Storage::disk('public')->exists($imagePath)) {
                            // Keep the full URL for the template to use
                            $element['image_url'] = $imageUrl;
                            $element['image'] = $imageUrl;
                            // Store the path for future reference
                            $element['image_path'] = $imagePath;
                            Log::info('Image data prepared:', [
                                'url' => $imageUrl,
                                'path' => $imagePath
                            ]);
                        } else {
                            Log::error('Image not found:', ['path' => $imagePath]);
                        }
                    } else {
                        Log::warning('Image URL is not a storage URL:', ['url' => $imageUrl]);
                    }
                } else {
                    Log::warning('No image URL found in element');
                }
            }

            Log::info('Processed element:', $element);
            return $element;
        }, $elements);
    }

    private function prepareElements($elements, $replacements)
    {
        return array_map(function($element) use ($replacements) {
            if ($element['type'] === 'text' && isset($element['placeholderType']) && $element['placeholderType'] !== 'custom') {
                $element['text'] = strtr($element['text'], $replacements);
            }
            return $element;
        }, $elements);
    }
}
