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
            // Ambil merchant_id dari user yang sedang login (misal relasi user->merchant_id)
            $merchantId = $request->user()->merchant_id ?? 1; // fallback ke 1 jika tidak ada

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'background_image' => 'required|string',
                'elements' => 'required|array',
                'certificate_number_format' => 'nullable|string',
                // Hapus 'merchant_id' dari validasi request
            ]);

            // Ubah URL gambar menjadi path penyimpanan
            $background_path = str_replace(Storage::url(''), '', $validated['background_image']);
            if (!Storage::disk('public')->exists($background_path)) {
                return response()->json(['status' => 'error', 'message' => 'Background image not found on storage.'], 404);
            }

            // Create template
            $template = new Sertifikat();
            $template->name = $validated['name'];
            $template->background_image = $background_path;
            $template->elements = $this->processElements($validated['elements']);
            $template->layout = [
                'width' => $this->pdfWidth,
                'height' => $this->pdfHeight,
                'orientation' => 'landscape'
            ];
            $template->merchant_id = $merchantId; // Set otomatis dari user

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
                $validated['elements'] = $this->processElements($validated['elements']);
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
     * Enhanced processElements method with shape support
     */
    private function processElements($elements)
    {
        return array_map(function ($element) {
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

                $allowedWeights = ['normal', '400', '500', '600', '700', 'bold'];
                $allowedFonts = [
                    // System
                    'Arial',
                    'Times New Roman',
                    'Helvetica',
                    'Georgia',
                    'Verdana',
                    'Courier New',
                    // Sans-Serif
                    'Inter',
                    'Poppins',
                    'Montserrat',
                    'Open Sans',
                    'League Spartan',
                    'DM Sans',
                    'Oswald',
                    'Barlow',
                    // Serif
                    'Playfair Display',
                    'Merriweather',
                    'Libre Baskerville',
                    'Lora',
                    'Bree Serif',
                    'DM Serif Display',
                    // Decorative
                    'Alice',
                    'Allura',
                    'Great Vibes',
                    'Dancing Script',
                    'Brittany',
                    'Breathing',
                    'Brighter',
                    'Bryndan Write',
                    'Caitlin Angelica',
                    'Railey',
                    'More Sugar',
                    // Display
                    'Bebas Neue',
                    'Anton',
                    'Archivo Black',
                    'Fredoka One'
                ];

                // Normalisasi font weight
                if (!in_array(strval($element['font']['weight']), $allowedWeights)) {
                    $element['font']['weight'] = '400';
                }

                // Normalisasi font family
                if (!in_array($element['font']['family'], $allowedFonts)) {
                    $element['font']['family'] = 'Arial';
                }

                // If the editor provided a folder, try to resolve a concrete font file (weightFile)
                // so saved template will reference the actual font file (including italic variants)
                try {
                    $folder = isset($element['font']['folder']) ? $element['font']['folder'] : null;
                    $requestedFile = isset($element['font']['weightFile']) ? $element['font']['weightFile'] : null;
                    $styleReq = isset($element['font']['style']) ? $element['font']['style'] : 'normal';
                    $weightReq = isset($element['font']['cssWeight']) ? $element['font']['cssWeight'] : (isset($element['font']['weight']) ? $element['font']['weight'] : '400');

                    if ($folder) {
                        $folderPath = public_path('fonts/' . $folder);
                        $resolved = null;

                        // If requestedFile looks like a filename and exists, keep it
                        if ($requestedFile && preg_match('/\.(ttf|otf|woff2?|woff)$/i', $requestedFile)) {
                            $cand = $folderPath . DIRECTORY_SEPARATOR . $requestedFile;
                            if (file_exists($cand)) {
                                $resolved = $requestedFile;
                            }
                        }

                        if (!$resolved && is_dir($folderPath)) {
                            $files = array_values(array_filter(scandir($folderPath), function($fn) use ($folderPath) {
                                if (in_array($fn, ['.', '..'])) return false;
                                return preg_match('/\.(ttf|otf|woff2?|woff)$/i', $fn) && is_file($folderPath . DIRECTORY_SEPARATOR . $fn);
                            }));

                            // Prefer italic file when italic requested
                            if ($styleReq === 'italic') {
                                foreach ($files as $ff) {
                                    if (stripos($ff, 'italic') !== false) { $resolved = $ff; break; }
                                }
                            }

                            // Match by weight tokens
                            if (!$resolved) {
                                foreach ($files as $ff) {
                                    $low = strtolower($ff);
                                    if (strpos($low, (string)$weightReq) !== false) { $resolved = $ff; break; }
                                    if ($weightReq == '400' && (strpos($low, 'regular') !== false || strpos($low, '-regular') !== false)) { $resolved = $ff; break; }
                                    if ($weightReq == '700' && (strpos($low, 'bold') !== false || strpos($low, '-bold') !== false)) { $resolved = $ff; break; }
                                    if ($weightReq == '600' && (strpos($low, 'semibold') !== false || strpos($low, 'semi') !== false)) { $resolved = $ff; break; }
                                }
                            }

                            // fallback to first
                            if (!$resolved && count($files) > 0) $resolved = $files[0];
                        }

                        if ($resolved) {
                            $element['font']['weightFile'] = $resolved;
                            // ensure folder is saved as provided (preserve case)
                            $element['font']['folder'] = $folder;
                        }
                    }
                } catch (\Exception $e) {
                    Log::warning('Font resolution failed for element', ['err' => $e->getMessage(), 'element' => $element]);
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

                // Validate shape type - now supports all frontend shapes
                $allowedShapes = [
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
                ];
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