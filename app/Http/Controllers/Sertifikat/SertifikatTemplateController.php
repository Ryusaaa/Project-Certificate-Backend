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
            'data' => $template,
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

            // Process text elements (existing logic)
            if ($element['type'] === 'text') {
                if (isset($element['fontSize'])) {
                    $element['fontSize'] = intval($element['fontSize']);
                    $element['fontSize'] = max(8, min($element['fontSize'], 72));
                }

                if (!isset($element['font']) || !is_array($element['font'])) {
                    $element['font'] = [
                        'family' => 'Arial',
                        'weight' => '400',
                        'style' => 'normal'
                    ];
                } else {
                    $element['font'] = array_merge([
                        'family' => 'Arial',
                        'weight' => '400',
                        'style' => 'normal'
                    ], $element['font']);
                }

                // Font validation logic (existing)...
                $allowedWeights = ['400', '500', '600', '700'];
                $allowedFonts = [
                    'Times New Roman', 'Arial', 'Helvetica', 'Georgia',
                    'Montserrat', 'Playfair Display', 'Poppins', 'Alice', 'Allura'
                ];

                if (!in_array($element['font']['weight'], $allowedWeights)) {
                    $element['font']['weight'] = '400';
                }

                if (!in_array($element['font']['family'], $allowedFonts)) {
                    $element['font']['family'] = 'Arial';
                }
            }

            // Process image elements (existing logic)
            if ($element['type'] === 'image') {
                $imageUrl = null;
                foreach (['imageUrl', 'url', 'image', 'src'] as $field) {
                    if (!empty($element[$field])) {
                        $imageUrl = $element[$field];
                        break;
                    }
                }
                
                if ($imageUrl) {
                    if (preg_match('#/storage/certificates/([^/]+)$#', $imageUrl, $matches)) {
                        $imagePath = 'certificates/' . $matches[1];
                        
                        if (Storage::disk('public')->exists($imagePath)) {
                            $element['image_url'] = $imageUrl;
                            $element['image'] = $imageUrl;
                            $element['image_path'] = $imagePath;
                        }
                    }
                }
            }

            // Process shape elements (NEW)
            if ($element['type'] === 'shape') {
                // Ensure shape has required properties
                $element['shapeType'] = $element['shapeType'] ?? 'rectangle';
                
                // Validate shape type
                $allowedShapes = ['line', 'square', 'circle', 'rectangle'];
                if (!in_array($element['shapeType'], $allowedShapes)) {
                    $element['shapeType'] = 'rectangle';
                }

                // Ensure style properties exist
                if (!isset($element['style']) || !is_array($element['style'])) {
                    $element['style'] = [];
                }

                $element['style'] = array_merge([
                    'color' => '#000000',
                    'fillColor' => 'transparent',
                    'strokeWidth' => 1,
                    'opacity' => 1,
                    'borderRadius' => 0
                ], $element['style']);

                // Validate style values
                $element['style']['strokeWidth'] = max(0, floatval($element['style']['strokeWidth']));
                $element['style']['opacity'] = max(0, min(1, floatval($element['style']['opacity'])));
                $element['style']['borderRadius'] = max(0, floatval($element['style']['borderRadius']));

                Log::info('Processed shape element:', $element);
            }

            // Remove any scaling-related properties
            unset($element['originalX'], $element['originalY']);
            unset($element['originalWidth'], $element['originalHeight']);

            return $element;
        }, $elements);
    }
}