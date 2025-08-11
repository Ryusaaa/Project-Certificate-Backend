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

    public function previewPDF(Request $request, $id)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'recipient_name' => 'required|string',
                'certificate_number' => 'required|string',
                'date' => 'required|date'
            ]);

            // Get template
            $template = Sertifikat::find($id);
            if (!$template) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Template sertifikat tidak ditemukan'
                ], 404);
            }
            
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

            // Generate temporary filename for preview
            $filename = sprintf(
                'preview_sertifikat_%s_%s_%s.pdf',
                Str::slug($validated['recipient_name']),
                Str::slug($validated['certificate_number']),
                now()->format('Ymd_His')
            );

            // Save to temporary storage
            $pdfPath = 'certificates/previews/' . $filename;
            Storage::disk('public')->put($pdfPath, $pdf->output());

            // Return preview URL that will expire
            $previewUrl = '/storage/' . $pdfPath;
            
            // Schedule file deletion after 1 hour
            dispatch(function() use ($pdfPath) {
                Storage::disk('public')->delete($pdfPath);
            })->delay(now()->addHour());

            return response()->json([
                'status' => 'success',
                'message' => 'Preview berhasil dibuat',
                'data' => [
                    'preview_url' => $previewUrl,
                    'expires_in' => '1 hour'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating preview PDF: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
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
            $template = Sertifikat::find($id);
            if (!$template) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Template sertifikat tidak ditemukan'
                ], 404);
            }
            
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

            // Calculate scale factor for PDF coordinates
            $scaleFactor = 1;  // Adjust this if needed based on your editor's coordinate system
            
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

            // For text elements, ensure font size is properly scaled
            if ($element['type'] === 'text' && isset($element['fontSize'])) {
                $element['fontSize'] = intval($element['fontSize']);
                // Ensure minimum and maximum font sizes
                $element['fontSize'] = max(8, min($element['fontSize'], 72));
            }

            // Remove any scaling-related properties
            unset($element['originalX'], $element['originalY']);
            unset($element['originalWidth'], $element['originalHeight']);

            // Validate and process font for text elements
            if ($element['type'] === 'text') {
                // Initialize font object with defaults if not set
                if (!isset($element['font']) || !is_array($element['font'])) {
                    $element['font'] = [
                        'family' => 'Arial',
                        'weight' => '400',
                        'style' => 'normal'
                    ];
                } else {
                    // Ensure all font properties exist with defaults
                    $element['font'] = array_merge([
                        'family' => 'Arial',
                        'weight' => '400',
                        'style' => 'normal'
                    ], $element['font']);
                }
                
                // Validate and clean font properties
                $allowedWeights = ['400', '500', '600', '700'];
                $allowedFonts = [
                    // System Fonts
                    'Times New Roman',
                    'Arial',
                    'Helvetica',
                    'Georgia',
                    // Custom Fonts
                    'Montserrat',
                    'Playfair Display',
                    'Poppins',
                    'Alice',
                    'Allura',
                    'Anonymous Pro',
                    'Anton',
                    'Arapey',
                    'Archivo Black',
                    'Arimo',
                    'Barlow',
                    'Bebas Neue',
                    'Belleza',
                    'Bree Serif',
                    'Bryndan Write',
                    'Chewy',
                    'Chunkfive Ex',
                    'Cormorant Garamond',
                    'DM Sans',
                    'DM Serif Display',
                    'Forum',
                    'Great Vibes',
                    'Hammersmith One',
                    'Inria Serif',
                    'Inter',
                    'League Gothic',
                    'League Spartan',
                    'Libre Baskerville',
                    'Lora',
                    'Merriweather',
                    'Nunito',
                    'Open Sans',
                    'Oswald',
                    'Questrial',
                    'Quicksand',
                    'Raleway',
                    'Roboto',
                    'Shrikhand',
                    'Tenor Sans',
                    'Yeseva One'
                ];

                // Validate font weight
                if (!in_array($element['font']['weight'], $allowedWeights)) {
                    $element['font']['weight'] = '400';
                    Log::warning('Invalid font weight specified, falling back to 400', [
                        'specified_weight' => $element['font']['weight'],
                        'allowed_weights' => $allowedWeights
                    ]);
                }

                // Validate font family
                if (!in_array($element['font']['family'], $allowedFonts)) {
                    $element['font']['family'] = 'Arial';
                    Log::warning('Invalid font family specified, falling back to Arial', [
                        'specified_font' => $element['font']['family'],
                        'allowed_fonts' => $allowedFonts
                    ]);
                }

                // Log font properties for debugging
                Log::info('Final font properties:', $element['font']);
            }

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

    public function downloadPDF($token)
    {
        try {
            // Find download record by token
            $download = CertificateDownload::where('token', $token)->first();
            
            if (!$download) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token tidak valid'
                ], 404);
            }

            // Check if download has expired
            if ($download->isExpired()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Link download telah kadaluarsa'
                ], 410);
            }

            $filepath = 'certificates/generated/' . $download->filename;
            
            // Check if file exists
            if (!Storage::disk('public')->exists($filepath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            // Increment download count
            $download->incrementDownloadCount();

            // Return file for download
            return response()->download(
                Storage::disk('public')->path($filepath),
                $download->filename,
                ['Content-Type' => 'application/pdf']
            );

        } catch (\Exception $e) {
            Log::error('Error downloading PDF: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengunduh file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function previewPDFWithToken($token)
    {
        try {
            // Find download record by token
            $download = CertificateDownload::where('token', $token)->first();
            
            if (!$download) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Token tidak valid'
                ], 404);
            }

            // Check if download has expired
            if ($download->isExpired()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Link preview telah kadaluarsa'
                ], 410);
            }

            $filepath = 'certificates/generated/' . $download->filename;
            
            // Check if file exists
            if (!Storage::disk('public')->exists($filepath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'File tidak ditemukan'
                ], 404);
            }

            // Return file for inline preview
            return response()->file(
                Storage::disk('public')->path($filepath),
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline'
                ]
            );

        } catch (\Exception $e) {
            Log::error('Error previewing PDF: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menampilkan file: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generateBulkPDF(Request $request, $id)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'recipients' => 'required|array|min:1',
                'recipients.*.recipient_name' => 'required|string',
                'recipients.*.certificate_number' => 'required|string',
                'recipients.*.date' => 'required|date',
                'recipients.*.email' => 'required|email' // tambah validasi email
            ]);

            // Get template
            $template = Sertifikat::find($id);
            if (!$template) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Template sertifikat tidak ditemukan'
                ], 404);
            }

            $generatedPDFs = [];
            
            foreach ($validated['recipients'] as $recipient) {
                // Format date for each recipient
                setlocale(LC_TIME, 'id_ID');
                Carbon::setLocale('id');
                $dateText = Carbon::parse($recipient['date'])->translatedFormat('d F Y');

                // Process template elements for each recipient
                $elements = $this->prepareElements($template->elements, [
                    '{NAMA}' => $recipient['recipient_name'],
                    '{NOMOR}' => $recipient['certificate_number'],
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

                // Generate PDF for each recipient
                $pdf = PDF::loadView('sertifikat.template', $data)
                         ->setPaper([0, 0, $this->pdfWidth, $this->pdfHeight], 'landscape');

                // Generate unique filename and token for each recipient
                $filename = sprintf(
                    'sertifikat_%s_%s_%s.pdf',
                    Str::slug($recipient['recipient_name']),
                    Str::slug($recipient['certificate_number']),
                    now()->format('Ymd_His')
                );
                
                $downloadToken = Str::random(64); // Generate secure token
                
                // Save to storage
                $pdfPath = 'certificates/generated/' . $filename;
                Storage::disk('public')->put($pdfPath, $pdf->output());

                // Create download record
                $download = $template->createDownload([
                    'token' => $downloadToken,
                    'filename' => $filename,
                    'recipient_name' => $recipient['recipient_name'],
                    'certificate_number' => $recipient['certificate_number'],
                    'user_id' => $request->user() ? $request->user()->id : null,
                    'expires_at' => now()->addDays(30) // Token berlaku 30 hari
                ]);

                // Find user by email and assign certificate
                $user = \App\Models\User::where('email', $recipient['email'])->first();
                if ($user) {
                    \App\Models\UserCertificate::create([
                        'user_id' => $user->id,
                        'certificate_download_id' => $download->id,
                        'assigned_at' => now(),
                        'status' => 'active'
                    ]);
                } else {
                    \Illuminate\Support\Facades\Log::warning('User not found for email: ' . $recipient['email']);
                }

                // Add to generated PDFs array
                $generatedPDFs[] = [
                    'recipient_name' => $recipient['recipient_name'],
                    'certificate_number' => $recipient['certificate_number'],
                    'view_url' => '/storage/' . $pdfPath,
                    'download_url' => '/sertifikat-templates/download/' . $downloadToken,
                    'download_token' => $downloadToken
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => count($generatedPDFs) . ' sertifikat berhasil dibuat',
                'data' => $generatedPDFs
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating bulk PDFs: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
