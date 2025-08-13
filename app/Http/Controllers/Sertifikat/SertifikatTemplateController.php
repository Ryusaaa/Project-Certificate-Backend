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
use App\Models\CertificateDownload;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class SertifikatTemplateController extends Controller
{
    private $pdfWidth = 842;    // A4 Landscape width
    private $pdfHeight = 595;   // A4 Landscape height

    // ... existing methods (uploadImage, index, show, destroy) tetap sama ...

    public function uploadImage(Request $request)
    {
        try {
            // Menentukan nama input dan pesan sukses berdasarkan file yang diupload
            if ($request->hasFile('background_image')) {
                $inputName = 'background_image';
                $successMessage = 'Background image uploaded successfully';
            } elseif ($request->hasFile('element_image')) {
                $inputName = 'element_image';
                $successMessage = 'Element image uploaded successfully';
            } else {
                // Jika tidak ada file yang dikirim, kembalikan error
                return response()->json([
                    'status' => 'error',
                    'message' => 'No image file found in the request.'
                ], 400);
            }

            Log::info('Starting image upload', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'input_name' => $inputName
            ]);

            // Validasi file yang sesuai
            $request->validate([
                $inputName => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $file = $request->file($inputName);
            Log::info('File details', [
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            // Logika penyimpanan file (tetap sama)
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('certificates', $filename, 'public');
            $url = '/storage/' . $path;

            Log::info('Image uploaded successfully', [
                'path' => $path,
                'public_url' => $url
            ]);

            // Mengembalikan response dengan pesan yang dinamis
            return response()->json([
                'status' => 'success',
                'url' => $url,
                'message' => $successMessage
            ]);

        } catch (ValidationException $e) {
            // Error khusus untuk validasi
            Log::error('Image upload validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Error umum lainnya
            Log::error('Error uploading image', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupload gambar: ' . $e->getMessage()
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
        Log::info('Creating new certificate template', [
            'request_data' => $request->all()
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'background_image' => 'required|string',
            'elements' => 'required|array'
        ]);

        Log::info('Elements before processing:', [
            'elements' => $validated['elements']
        ]);

        $background_image = $validated['background_image'];
        if (preg_match('#/storage/certificates/([^/]+)$#', $background_image, $matches)) {
            $background_image = 'certificates/' . $matches[1];
        }

        if (!Storage::disk('public')->exists($background_image)) {
            throw new \Exception('Background image not found');
        }

        $processedElements = $this->processElements($validated['elements']);
        
        Log::info('Elements after processing:', [
            'elements' => $processedElements
        ]);

        // Create template
        $template = new Sertifikat();
        $template->name = $validated['name'];
        $template->background_image = $background_image;
        $template->elements = $processedElements;
        $template->layout = [
            'width' => $this->pdfWidth,
            'height' => $this->pdfHeight,
            'orientation' => 'landscape'
        ];

        if (!$template->save()) {
            throw new \Exception('Failed to save template');
        }

        // ✅ Log template yang tersimpan
        Log::info('Template saved successfully:', [
            'template_id' => $template->id,
            'elements_count' => count($template->elements),
            'saved_elements' => $template->elements
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Template created successfully',
            'data' => $template,
        ], 201);

    } catch (\Exception $e) {
        Log::error('Error creating template: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);
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

    // NEW METHODS FOR SHAPE MANAGEMENT
    
    /**
     * Add shape to certificate template
     */
    public function addShape(Request $request, $id)
    {
        try {
            $template = Sertifikat::findOrFail($id);

            $validated = $request->validate([
                'type' => ['required', Rule::in(['line', 'square', 'circle', 'rectangle'])],
                'x' => 'required|numeric',
                'y' => 'required|numeric',
                'width' => 'required|numeric|min:1',
                'height' => 'required|numeric|min:1',
                'rotation' => 'numeric',
                'style' => 'array',
                'style.color' => 'string',
                'style.fillColor' => 'string',
                'style.strokeWidth' => 'numeric|min:0',
                'style.opacity' => 'numeric|min:0|max:1',
                'style.borderRadius' => 'numeric|min:0',
                'zIndex' => 'integer'
            ]);

            // Get current elements
            $elements = $template->elements ?? [];

            // Create new shape element
            $newShape = [
                'id' => 'shape_' . Str::random(8),
                'type' => 'shape',
                'shapeType' => $validated['type'],
                'x' => $validated['x'],
                'y' => $validated['y'],
                'width' => $validated['width'],
                'height' => $validated['height'],
                'rotation' => $validated['rotation'] ?? 0,
                'style' => array_merge([
                    'color' => '#000000',
                    'fillColor' => 'transparent',
                    'strokeWidth' => 1,
                    'opacity' => 1,
                    'borderRadius' => 0
                ], $validated['style'] ?? []),
                'zIndex' => $validated['zIndex'] ?? $this->getNextZIndex($elements),
                'isVisible' => true
            ];

            // Add to elements
            $elements[] = $newShape;

            // Update template
            $template->elements = $elements;
            $template->save();

            Log::info('Shape added successfully', ['shape_id' => $newShape['id'], 'template_id' => $id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Shape added successfully',
                'data' => $newShape
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error adding shape: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update shape in certificate template
     */
    public function updateShape(Request $request, $id, $shapeId)
    {
        try {
            $template = Sertifikat::findOrFail($id);

            $validated = $request->validate([
                'type' => [Rule::in(['line', 'square', 'circle', 'rectangle'])],
                'x' => 'numeric',
                'y' => 'numeric', 
                'width' => 'numeric|min:1',
                'height' => 'numeric|min:1',
                'rotation' => 'numeric',
                'style' => 'array',
                'style.color' => 'string',
                'style.fillColor' => 'string',
                'style.strokeWidth' => 'numeric|min:0',
                'style.opacity' => 'numeric|min:0|max:1',
                'style.borderRadius' => 'numeric|min:0',
                'zIndex' => 'integer',
                'isVisible' => 'boolean'
            ]);

            $elements = $template->elements ?? [];
            $shapeFound = false;

            // Find and update the shape
            foreach ($elements as &$element) {
                if ($element['id'] === $shapeId && $element['type'] === 'shape') {
                    // Update only provided fields
                    foreach ($validated as $key => $value) {
                        if ($key === 'type') {
                            $element['shapeType'] = $value;
                        } elseif ($key === 'style' && is_array($value)) {
                            $element['style'] = array_merge($element['style'] ?? [], $value);
                        } else {
                            $element[$key] = $value;
                        }
                    }
                    $shapeFound = true;
                    break;
                }
            }

            if (!$shapeFound) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shape not found'
                ], 404);
            }

            $template->elements = $elements;
            $template->save();

            Log::info('Shape updated successfully', ['shape_id' => $shapeId, 'template_id' => $id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Shape updated successfully',
                'data' => $element
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating shape: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete shape from certificate template
     */
    public function deleteShape($id, $shapeId)
    {
        try {
            $template = Sertifikat::findOrFail($id);
            $elements = $template->elements ?? [];
            
            // Filter out the shape to delete
            $filteredElements = array_filter($elements, function($element) use ($shapeId) {
                return !($element['id'] === $shapeId && $element['type'] === 'shape');
            });

            // Check if any element was removed
            if (count($filteredElements) === count($elements)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shape not found'
                ], 404);
            }

            $template->elements = array_values($filteredElements); // Re-index array
            $template->save();

            Log::info('Shape deleted successfully', ['shape_id' => $shapeId, 'template_id' => $id]);

            return response()->json([
                'status' => 'success',
                'message' => 'Shape deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error deleting shape: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all shapes from certificate template
     */
    public function getShapes($id)
    {
        try {
            $template = Sertifikat::findOrFail($id);
            $elements = $template->elements ?? [];

            // Filter only shape elements
            $shapes = array_filter($elements, function($element) {
                return $element['type'] === 'shape';
            });

            // Sort by zIndex
            usort($shapes, function($a, $b) {
                return ($a['zIndex'] ?? 0) <=> ($b['zIndex'] ?? 0);
            });

            return response()->json([
                'status' => 'success',
                'data' => array_values($shapes)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Template not found'
            ], 404);
        }
    }

    /**
     * Update shapes order (z-index)
     */
    public function updateShapesOrder(Request $request, $id)
    {
        try {
            $template = Sertifikat::findOrFail($id);
            
            $validated = $request->validate([
                'shapes' => 'required|array',
                'shapes.*.id' => 'required|string',
                'shapes.*.zIndex' => 'required|integer'
            ]);

            $elements = $template->elements ?? [];

            // Update z-index for shapes
            foreach ($validated['shapes'] as $shapeData) {
                foreach ($elements as &$element) {
                    if ($element['id'] === $shapeData['id'] && $element['type'] === 'shape') {
                        $element['zIndex'] = $shapeData['zIndex'];
                        break;
                    }
                }
            }

            $template->elements = $elements;
            $template->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Shapes order updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating shapes order: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // HELPER METHODS

    /**
     * Get next available z-index
     */
    private function getNextZIndex($elements)
    {
        $maxZIndex = 0;
        foreach ($elements as $element) {
            if (isset($element['zIndex']) && $element['zIndex'] > $maxZIndex) {
                $maxZIndex = $element['zIndex'];
            }
        }
        return $maxZIndex + 1;
    }

    /**
     * Enhanced processElements method with shape support
     */

private function processElements($elements)
{
    return array_map(function($element) {
        Log::info('Processing element:', $element);

        // Calculate scale factor for PDF coordinates
        $scaleFactor = 1;
        
        // Ensure coordinates are within bounds and properly scaled
        $element['x'] = max(0, min($element['x'] * $scaleFactor, $this->pdfWidth));
        $element['y'] = max(0, min($element['y'] * $scaleFactor, $this->pdfHeight));

        // Scale size properties if they exist
        if (isset($element['width'])) {
            $element['width'] = $element['width'] * $scaleFactor;
        }
        if (isset($element['height'])) {
            $element['height'] = $element['height'] * $scaleFactor;
        }

        // Process text elements (existing logic tetap sama)
        if ($element['type'] === 'text') {
            // ... existing text processing code ...
        }

        // Process image elements (existing logic tetap sama)
        if ($element['type'] === 'image') {
            // ... existing image processing code ...
        }

        // FIXED: Process shape elements with consistent property names
        if ($element['type'] === 'shape') {
            $element['shapeType'] = $element['shapeType'] ?? 'rectangle';
            
            $allowedShapes = [
                'rectangle', 'circle', 'triangle', 'star', 'diamond', 
                'pentagon', 'hexagon', 'line', 'arrow', 'heart', 'cross'
            ];
            
            if (!in_array($element['shapeType'], $allowedShapes)) {
                $element['shapeType'] = 'rectangle';
            }

            // FIXED: Use consistent property names that match React frontend
            if (!isset($element['style']) || !is_array($element['style'])) {
                $element['style'] = [];
            }

            // Map frontend properties to consistent backend structure
            $element['style'] = array_merge([
                'fillColor' => $element['fillColor'] ?? 'transparent',
                'strokeColor' => $element['strokeColor'] ?? '#000000',  // FIXED: Use strokeColor consistently
                'strokeWidth' => floatval($element['strokeWidth'] ?? 1),
                'opacity' => floatval($element['opacity'] ?? 1),
                'borderRadius' => floatval($element['borderRadius'] ?? 0)
            ], $element['style']);

            // Validate values
            $element['style']['strokeWidth'] = max(0, floatval($element['style']['strokeWidth']));
            $element['style']['opacity'] = max(0, min(1, floatval($element['style']['opacity'])));
            $element['style']['borderRadius'] = max(0, floatval($element['style']['borderRadius']));

            // Ensure required properties exist
            if (!isset($element['zIndex'])) {
                $element['zIndex'] = 0;
            }
            
            if (!isset($element['isVisible'])) {
                $element['isVisible'] = true;
            }

            if (!isset($element['width']) || $element['width'] <= 0) {
                $element['width'] = 100;
            }
            if (!isset($element['height']) || $element['height'] <= 0) {
                $element['height'] = 100;
            }

            Log::info('Processed shape element:', $element);
        }

        if (!isset($element['rotation'])) {
            $element['rotation'] = 0;
        }
        if (!isset($element['scaleX'])) {
            $element['scaleX'] = 1;
        }
        if (!isset($element['scaleY'])) {
            $element['scaleY'] = 1;
        }

        unset($element['originalX'], $element['originalY']);
        unset($element['originalWidth'], $element['originalHeight']);

        return $element;
    }, $elements);
}
}