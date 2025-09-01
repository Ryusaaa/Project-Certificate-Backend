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
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SertifikatTemplateController extends Controller
{

    private function getAllFonts()
    {
        $fontsDir = public_path('fonts');
        $fonts = [];

        if (!is_dir($fontsDir))
            return $fonts;

        foreach (scandir($fontsDir) as $fontFamily) {
            if ($fontFamily === '.' || $fontFamily === '..')
                continue;
            $familyPath = $fontsDir . DIRECTORY_SEPARATOR . $fontFamily;
            if (is_dir($familyPath)) {
                $fonts[$fontFamily] = [];
                foreach (scandir($familyPath) as $fontFile) {
                    if (in_array($fontFile, ['.', '..']))
                        continue;
                    $ext = pathinfo($fontFile, PATHINFO_EXTENSION);
                    if (in_array(strtolower($ext), ['ttf', 'otf', 'woff', 'woff2'])) {
                        $fonts[$fontFamily][] = $fontFile;
                    }
                }
            }
        }
        return $fonts;
    }

    /**
     * Return available weight variants for a font folder in public/fonts/{font}
     * Response: JSON array of objects: [{ key: "regular", file: "Font-Regular.ttf" }, ...]
     */
    public function fontWeights($font)
    {
        // Prevent path traversal and build folder path
        $fontFolder = basename(urldecode($font));
        $dir = public_path('fonts' . DIRECTORY_SEPARATOR . $fontFolder);

        if (!is_dir($dir)) {
            // return empty array (200) so client can fallback gracefully
            return response()->json([]);
        }

        $files = array_values(array_filter(scandir($dir), function ($f) use ($dir) {
            if ($f === '.' || $f === '..') return false;
            $full = $dir . DIRECTORY_SEPARATOR . $f;
            if (!is_file($full)) return false;
            return preg_match('/\.(ttf|otf|woff2?|woff)$/i', $f);
        }));

        // Map logical keys to tokens and css weight
        $mapping = [
            'regular'  => ['css' => '400', 'tokens' => ['regular', '-regular']],
            'medium'   => ['css' => '500', 'tokens' => ['medium', '-medium']],
            'semibold' => ['css' => '600', 'tokens' => ['semibold', 'semi-bold', 'semi_bold', '-semibold']],
            'bold'     => ['css' => '700', 'tokens' => ['bold', '-bold']]
        ];

        $found = [];

        foreach ($files as $file) {
            $low = strtolower($file);

            // skip italic files here; style/italic controlled by client
            if (strpos($low, 'italic') !== false) {
                continue;
            }

            foreach ($mapping as $key => $info) {
                foreach ($info['tokens'] as $token) {
                    if (strpos($low, $token) !== false) {
                        if (!isset($found[$key])) {
                            $found[$key] = [
                                'key' => $key,
                                'file' => $file,
                                'cssWeight' => $info['css'],
                                'label' => ucfirst(str_replace(['-', '_'], ' ', $key)),
                                'style' => 'normal'
                            ];
                        }
                    }
                }
            }

            // If no token matched, try numeric weight detection
            if (preg_match('/\b(100|200|300|400|500|600|700|800|900)\b/i', $low, $m)) {
                $num = intval($m[1]);
                $key = null;
                if ($num === 400) $key = 'regular';
                elseif ($num === 500) $key = 'medium';
                elseif ($num === 600) $key = 'semibold';
                elseif ($num === 700) $key = 'bold';
                if ($key && !isset($found[$key])) {
                    $found[$key] = [
                        'key' => $key,
                        'file' => $file,
                        'cssWeight' => (string)$num,
                        'label' => ucfirst(str_replace(['-', '_'], ' ', $key)),
                        'style' => 'normal'
                    ];
                }
            }
        }

        // Keep only allowed order
        $order = ['regular', 'medium', 'semibold', 'bold'];
        $result = [];
        foreach ($order as $k) {
            if (isset($found[$k])) $result[] = $found[$k];
        }

        // Fallback: if nothing found, use first available file as regular
        if (empty($result) && count($files) > 0) {
            $first = $files[0];
            $result[] = [
                'key' => 'regular',
                'file' => $first,
                'cssWeight' => '400',
                'label' => 'Regular',
                'style' => 'normal'
            ];
        }

        return response()->json($result);
    }

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
                'message' => 'Image uploaded successfully',
                'status' => 'success',
                'url' => Storage::url($path)
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
            $merchantId = $request->user()->merchant_id ?? 1;

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'background_image' => 'required|string',
                'elements' => 'required|array',
                'certificate_number_format' => 'nullable|string',
            ]);

            $background_path = str_replace(Storage::url(''), '', $validated['background_image']);
            if (!Storage::disk('public')->exists($background_path)) {
                return response()->json(['status' => 'error', 'message' => 'Background image not found on storage.'], 404);
            }

            $template = new Sertifikat();
            $template->name = $validated['name'];
            $template->background_image = $background_path;

            $processedElements = array_map(function($element) {
                if ($element['type'] === 'image' && !empty($element['imageUrl'])) {
                    $element['imageUrl'] = str_replace(Storage::url(''), '', $element['imageUrl']);
                }
                return $element;
            }, $validated['elements']);

            $template->elements = $processedElements;
            
            $template->layout = [
                'width' => $this->pdfWidth,
                'height' => $this->pdfHeight,
                'orientation' => 'landscape'
            ];
            $template->merchant_id = $merchantId;

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
                'certificate_number_format' => 'sometimes|nullable|string',
                'merchant_id' => 'sometimes|required|exists:merchants,id'
            ]);

            if (isset($validated['background_image'])) {
                $validated['background_image'] = str_replace(Storage::url(''), '', $validated['background_image']);
            }
            if (isset($validated['elements'])) {
                $validated['elements'] = $this->processElements($validated['elements'], []);
            }

            // Validate certificate number format if provided
            if (isset($validated['certificate_number_format'])) {
                if (
                    $validated['certificate_number_format'] !== null &&
                    !preg_match('/X+/', $validated['certificate_number_format'])
                ) {
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
                'type' => [
                    \Illuminate\Validation\Rule::in([
                        'rectangle',
                        'circle',
                        'triangle',
                        'star',
                        'diamond',
                        'pentagon',
                        'hexagon',
                        'line',
                        'arrow',
                        'heart',
                        'cross'
                    ])
                ],
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
            $filteredElements = array_filter($elements, function ($element) use ($shapeId) {
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
            $shapes = array_filter($elements, function ($element) {
                return $element['type'] === 'shape';
            });

            // Sort by zIndex
            usort($shapes, function ($a, $b) {
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
     * Process elements with scale compensation for DomPDF
     */
    private function processElements($elements, $replacements = [], $scale = 1) 
    {
        if (!is_array($elements)) {
            return [];
        }

        // Faktor skala untuk mengompensasi konversi px ke pt di DomPDF (1px = 0.75pt, jadi multiply 96/72)
        $scaleFactor = 96 / 72; // ≈1.333

        // Preview dimensions (assumed to be the same as PDF for 1:1 scale)
        $previewWidth = 842;  // A4 Landscape width in px (editor)
        $previewHeight = 595; // A4 Landscape height in px (editor)

        return array_map(function($element) use ($replacements, $scale, $previewWidth, $previewHeight, $scaleFactor) {
            // Deep clone element
            $element = json_decode(json_encode($element), true);
            
            // Ensure we have all necessary dimensions
            $element['x'] = isset($element['x']) ? floatval($element['x']) : 0;
            $element['y'] = isset($element['y']) ? floatval($element['y']) : 0;
            $element['width'] = isset($element['width']) ? floatval($element['width']) : 0;
            $element['height'] = isset($element['height']) ? floatval($element['height']) : 0;

            // Calculate relative positions (proportional)
            $element['x'] = ($element['x'] / $previewWidth) * $this->pdfWidth;
            $element['y'] = ($element['y'] / $previewHeight) * $this->pdfHeight;
            $element['width'] = ($element['width'] / $previewWidth) * $this->pdfWidth;
            $element['height'] = ($element['height'] / $previewHeight) * $this->pdfHeight;

            // Apply DomPDF scale compensation (buat nilai CSS px lebih besar agar render ke pt benar)
            $element['x'] *= $scaleFactor;
            $element['y'] *= $scaleFactor;
            $element['width'] *= $scaleFactor;
            $element['height'] *= $scaleFactor;

            // Process font size for text elements (juga apply scale)
            if ($element['type'] === 'text') {
                $element['fontSize'] = isset($element['fontSize']) ? floatval($element['fontSize']) * $scaleFactor : 16 * $scaleFactor;
                if (isset($element['text'])) {
                    foreach ($replacements as $key => $value) {
                        if (strpos($element['text'], $key) !== false) {
                            $element['text'] = str_replace($key, $value, $element['text']);
                        }
                    }
                }
            }

            // Process image elements (apply scale to dimensions)
            if ($element['type'] === 'image') {
                // Sudah ada handling di blade, tapi pastikan dimensions adjusted
                $element['width'] = max(10, $element['width']); // Minimal size agar tidak hilang
                $element['height'] = max(10, $element['height']);
            }


            // Process QR code elements (tingkatkan size generate untuk quality full, apply scale)
            if ($element['type'] === 'qrcode') {
                $certificateNumber = $element['content'] ?? $replacements['{NOMOR}'] ?? '';
                if ($certificateNumber) {
                    // Create verification URL
                    $qrCodeContent = env('FRONTEND_URL') . '/sertifikat-templates/verify/' . $certificateNumber;
                    
                    // Calculate QR code size based on element dimensions (desired pt, lalu scale untuk generate)
                    $qrSize = min($element['width'], $element['height']) / $scaleFactor; // Kembali ke desired pt
                    
                    // Generate high-quality QR code with larger size untuk sharpness saat di-PDF
                    $qrCode = QrCode::format('png')
                        ->size(max(600, intval($qrSize * 4))) // Tingkatkan dari *2 ke *4 untuk full quality
                        ->margin(0) // No margin
                        ->errorCorrection('H') // Highest error correction
                        ->backgroundColor(255, 255, 255, 0) // Transparent
                        ->color(0, 0, 0)
                        ->generate($qrCodeContent);
                    
                    $element['qrcode'] = 'data:image/png;base64,' . base64_encode($qrCode);
                    
                    // Ensure QR code position is valid and centered
                    $element['x'] = max(0, min($element['x'], $this->pdfWidth * $scaleFactor - $element['width']));
                    $element['y'] = max(0, min($element['y'], $this->pdfHeight * $scaleFactor - $element['height']));
                }
            }

            // Process shape elements - normalize and log
            if ($element['type'] === 'shape') {
                // Ensure all shape properties are properly set
                $element['shapeType'] = $element['shapeType'] ?? 'rectangle';
                
                // Normalize style properties
                if (!isset($element['style'])) {
                    $element['style'] = [];
                }
                
                // Map individual properties to style object for consistency
                $styleProps = ['fillColor', 'strokeColor', 'color', 'strokeWidth', 'opacity', 'borderRadius'];
                foreach ($styleProps as $prop) {
                    if (isset($element[$prop])) {
                        $element['style'][$prop] = $element[$prop];
                    }
                }
                
                // Set default values
                $element['style']['fillColor'] = $element['style']['fillColor'] ?? 'transparent';
                $element['style']['strokeColor'] = $element['style']['strokeColor'] ?? $element['style']['color'] ?? '#000000';
                $element['style']['strokeWidth'] = $element['style']['strokeWidth'] ?? 1;
                $element['style']['opacity'] = $element['style']['opacity'] ?? 1;
                $element['style']['borderRadius'] = $element['style']['borderRadius'] ?? 0;
                
                // Ensure visibility
                $element['isVisible'] = $element['isVisible'] ?? true;
                $element['zIndex'] = $element['zIndex'] ?? 1;
                
                Log::info('Processing shape element', [
                    'id' => $element['id'] ?? 'unknown',
                    'shapeType' => $element['shapeType'],
                    'style' => $element['style'],
                    'dimensions' => [
                        'x' => $element['x'],
                        'y' => $element['y'], 
                        'width' => $element['width'],
                        'height' => $element['height']
                    ]
                ]);
            }

            // Process QR code elements (tingkatkan size generate untuk quality full, apply scale)
            if ($element['type'] === 'qrcode') {
                $certificateNumber = $element['content'] ?? $replacements['{NOMOR}'] ?? '';
                if ($certificateNumber) {
                    // Create verification URL
                    $qrCodeContent = env('FRONTEND_URL') . '/sertifikat-templates/verify/' . $certificateNumber;
                    
                    // Calculate QR code size based on element dimensions (desired pt, lalu scale untuk generate)
                    $qrSize = min($element['width'], $element['height']) / $scaleFactor; // Kembali ke desired pt
                    
                    // Generate high-quality QR code with larger size untuk sharpness saat di-PDF
                    $qrCode = QrCode::format('png')
                        ->size(max(600, intval($qrSize * 4))) // Tingkatkan dari *2 ke *4 untuk full quality
                        ->margin(0) // No margin
                        ->errorCorrection('H') // Highest error correction
                        ->backgroundColor(255, 255, 255, 0) // Transparent
                        ->color(0, 0, 0)
                        ->generate($qrCodeContent);
                    
                    $element['qrcode'] = 'data:image/png;base64,' . base64_encode($qrCode);
                    
                    // Ensure QR code position is valid and centered
                    $element['x'] = max(0, min($element['x'], $this->pdfWidth * $scaleFactor - $element['width']));
                    $element['y'] = max(0, min($element['y'], $this->pdfHeight * $scaleFactor - $element['height']));
                }
            }

            return $element;
        }, $elements);
    }
}
