<?php

namespace App\Http\Controllers\Sertifikat;

use App\Http\Controllers\Controller;
use App\Models\Sertifikat;
use App\Models\CertificateDownload;
use App\Models\UserCertificate;
use App\Models\User;
use App\Mail\CertificateGenerated;
use App\Models\DataActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class SertifikatPesertaController extends Controller
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

    private $pdfWidth = 842;    // A4 Landscape width
    private $pdfHeight = 595;   // A4 Landscape height

    private function processElements($elements, $replacements, $scale = 1)
    {
        if (!is_array($elements)) {
            return [];
        }
        $scaleFactor = 96 / 72; // ≈1.333 untuk kompensasi px ke pt
        $previewWidth = 842; // A4 Landscape width in px
        $previewHeight = 595; // A4 Landscape height in px
        return array_map(function($element) use ($replacements, $scale, $previewWidth, $previewHeight, $scaleFactor) {
            $element = json_decode(json_encode($element), true);
            // Ensure dimensions
            $element['x'] = isset($element['x']) ? floatval($element['x']) : 0;
            $element['y'] = isset($element['y']) ? floatval($element['y']) : 0;
            $element['width'] = isset($element['width']) ? floatval($element['width']) : 100; // Default size
            $element['height'] = isset($element['height']) ? floatval($element['height']) : 100;
            // Calculate relative positions and apply scale factor
            $element['x'] = ($element['x'] / $previewWidth) * $this->pdfWidth * $scaleFactor;
            $element['y'] = ($element['y'] / $previewHeight) * $this->pdfHeight * $scaleFactor;
            $element['width'] = ($element['width'] / $previewWidth) * $this->pdfWidth * $scaleFactor;
            $element['height'] = ($element['height'] / $previewHeight) * $this->pdfHeight * $scaleFactor;
            // Process text elements
            if ($element['type'] === 'text') {
                $element['fontSize'] = isset($element['fontSize']) ? floatval($element['fontSize']) * $scaleFactor : 16 * $scaleFactor;
                if (isset($element['text'])) {
                    $originalText = $element['text'];
                    foreach ($replacements as $key => $value) {
                        if (strpos($originalText, $key) !== false) {
                            $element['text'] = str_replace($key, $value, $originalText);
                        }
                    }
                }
            }
            // Process image elements
            if ($element['type'] === 'image') {
                $element['width'] = max(10, $element['width']); // Minimal size
                $element['height'] = max(10, $element['height']);
                if (isset($element['image_url']) && !empty($element['image_url'])) {
                    // Ensure image fits container
                    $element['src'] = $element['image_url'];
                }
            }
            // Process QR code elements
            if ($element['type'] === 'qrcode') {
                $certificateNumber = $element['content'] ?? $replacements['{NOMOR}'] ?? '';
                if ($certificateNumber) {
                    $qrCodeContent = env('FRONTEND_URL') . '/sertifikat-templates/verify/' . $certificateNumber;
                    $qrSize = min($element['width'], $element['height']) / $scaleFactor; // Convert back to desired pt
                    $qrCode = QrCode::format('png')
                        ->size(max(800, intval($qrSize * 6))) // Tingkatkan ke 6 untuk kualitas maksimal
                        ->margin(0)
                        ->errorCorrection('H')
                        ->backgroundColor(255, 255, 255, 0)
                        ->color(0, 0, 0)
                        ->generate($qrCodeContent);
                    $element['qrcode'] = 'data:image/png;base64,' . base64_encode($qrCode);
                    $element['width'] = $qrSize * $scaleFactor; // Adjust back to scaled size
                    $element['height'] = $qrSize * $scaleFactor;
                    $element['x'] = max(0, min($element['x'], $this->pdfWidth * $scaleFactor - $element['width']));
                    $element['y'] = max(0, min($element['y'], $this->pdfHeight * $scaleFactor - $element['height']));
                }
            }
            return $element;
        }, $elements);
    }

    public function previewPDF(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'recipient_name' => 'required|string',
                'certificate_number' => 'required|string',
                'date' => 'required|date',
                'merchant_id' => 'required|exists:merchants,id',
                'instruktur' => 'nullable|string'
            ]);
            $template = Sertifikat::find($id);
            if (!$template) {
                return response()->json(['status' => 'error', 'message' => 'Template sertifikat tidak ditemukan'], 404);
            }
            setlocale(LC_TIME, 'id_ID');
            Carbon::setLocale('id');
            $dateText = Carbon::parse($validated['date'])->translatedFormat('d F Y');
            $templateElements = is_array($template->elements) ? $template->elements : [];
            $replacements = [
                '{NAMA}' => $validated['recipient_name'],
                '{NOMOR}' => $validated['certificate_number'],
                '{TANGGAL}' => $dateText,
                '{INSTRUKTUR}' => $validated['instruktur'] ?? ''
            ];
            $elements = $this->processElements($templateElements, $replacements, 1);
            $data = [
                'template' => $template,
                'elements' => $elements,
                'background_image' => Storage::disk('public')->path($template->background_image),
                'pageWidth' => $this->pdfWidth,
                'pageHeight' => $this->pdfHeight,
                'replacements' => $replacements // Tambah untuk view
            ];
            $pdf = Pdf::loadView('pdf.template', $data);
            return $pdf->stream('sertifikat.pdf');
        } catch (\Exception $e) {
            Log::error('Error generating PDF: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function generatePDF(Request $request, $id)
    {
        try {
            // Validate request - simplified for preview
            $validated = $request->validate([
                'recipient_name' => 'required|string',
                'certificate_number' => 'required|string',
                'date' => 'required|date',
                'merchant_id' => 'required|exists:merchants,id',
                'instruktur' => 'required|string'  // Memastikan instruktur wajib diisi
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
            
            // Get and validate template elements
            $templateElements = is_array($template->elements) ? $template->elements : [];
            
            // Process elements with replacements
            $replacements = [
                '{NAMA}' => $validated['recipient_name'],
                '{NOMOR}' => $validated['certificate_number'],
                '{TANGGAL}' => $dateText,
                '{INSTRUKTUR}' => $validated['instruktur']
            ];
            
            // Process elements using 1:1 scale since we're using relative positioning
            $elements = $this->processElements($templateElements, $replacements, 1);
            // Process template elements
            $replacements = [
                '{NAMA}' => $validated['recipient_name'],
                '{NOMOR}' => $validated['certificate_number'],
                '{TANGGAL}' => $dateText,
                '{INSTRUKTUR}' => $validated['instruktur']
            ];
            $elements = $this->processElements($templateElements, $replacements);

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
                'preview_sertifikat_%s_%s.pdf',
                Str::slug($validated['recipient_name']),
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

    private function generateQRCode($text) {
        return QrCode::format('png')
            ->size(400) // Ukuran besar untuk kualitas bagus
            ->margin(0) // Hapus margin
            ->errorCorrection('H') // High error correction
            ->backgroundColor(255, 255, 255) // Background putih
            ->color(0, 0, 0) // QR hitam
            ->generate($text);
    }

    private function prepareElements($elements, $replacements)
    {
        if (!is_array($elements)) {
            return [];
        }

        return array_map(function($element) use ($replacements) {
            $element = json_decode(json_encode($element), true);
            
            if ($element['type'] === 'text') {
                $element['text'] = str_replace(array_keys($replacements), array_values($replacements), $element['text']);
            }

            if ($element['type'] === 'qrcode') {
                $certificateNumber = $replacements['{NOMOR}'] ?? '';
                if ($certificateNumber) {
                    $qrCodeContent = env('FRONTEND_URL') . '/sertifikat-templates/verify/' . $certificateNumber;
                    $qrCode = QrCode::format('png')
                        ->size(500)
                        ->margin(0)
                        ->errorCorrection('H')
                        ->generate($qrCodeContent);
                    $element['qrcode'] = 'data:image/png;base64,' . base64_encode($qrCode);
                }
            }

            if ($element['type'] === 'image' && !empty($element['imageUrl'])) {
                 $path = str_replace(Storage::url(''), '', $element['imageUrl']);
                 if(Storage::disk('public')->exists($path)){
                    $element['image_path'] = $path;
                 }
            }

            return $element;
        }, $elements);
    }

    public function getQRCodeFromCertificate($certificateNumber)
    {
        try {
            if (empty($certificateNumber)) {
                return '';
            }

            // Generate QR code URL
            $qrCodeContent = env('FRONTEND_URL') . '/sertifikat-templates/verify/' . $certificateNumber;
            
            // Generate QR code with high quality settings
            $qrCode = QrCode::format('png')
                ->size(500) 
                ->margin(0) // No margin
                ->errorCorrection('H') 
                ->backgroundColor(255, 255, 255)
                ->color(0, 0, 0)
                ->generate($qrCodeContent);

            // Return as base64 data URI
            return 'data:image/png;base64,' . base64_encode($qrCode);
        } catch (\Exception $e) {
            Log::error('Error generating QR code: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return '';
        }
    }

    private function generateCertificateNumber($template, $format = null)
    {
        try {
            // Use template's format if no format provided
            $format = $format ?? $template->certificate_number_format;

            if (empty($format)) {
                throw new \Exception('Format nomor sertifikat belum diatur');
            }

            // Find sequence of X's that come before a separator or end of string
            // This will match X's that are:
            // 1. At the start of the string and followed by a separator
            // 2. After a separator and followed by another separator
            // 3. At the end of string after a separator
            preg_match_all('/(?:^|[\/\-_])([X]+)(?=[\/\-_]|$)/', $format, $matches, PREG_OFFSET_CAPTURE);
            
            if (empty($matches[1])) {
                throw new \Exception('Format harus mengandung minimal satu sequence X yang valid (sebelum pemisah / - _)');
            }

            // Get the first valid sequence of X's as the increment placeholder
            $placeholder = $matches[1][0][0];
            $placeholderPosition = $matches[1][0][1];
            $placeholderLength = strlen($placeholder);
            
            Log::info('Found placeholder', [
                'placeholder' => $placeholder,
                'position' => $placeholderPosition,
                'length' => $placeholderLength,
                'format' => $format
            ]);
            
            // Get next number from template
            $nextNumber = $template->last_certificate_number + 1;

            // Update the last number in template
            $template->update([
                'last_certificate_number' => $nextNumber
            ]);

            // Format the number with leading zeros
            $formattedNumber = str_pad($nextNumber, $placeholderLength, '0', STR_PAD_LEFT);
            
            // Replace the X sequence at the exact position
            $beforeX = substr($format, 0, $placeholderPosition);
            $afterX = substr($format, $placeholderPosition + $placeholderLength);
            $certificateNumber = $beforeX . $formattedNumber . $afterX;

            return $certificateNumber;
        } catch (\Exception $e) {
            Log::error('Error generating certificate number: ' . $e->getMessage());
            throw $e;
        }
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
            $validated = $request->validate([
                'recipients' => 'required|array|min:1',
                'recipients.*.recipient_name' => 'required|string',
                'recipients.*.date' => 'required|date',
                'recipients.*.email' => 'required|email',
                'certificate_number_format' => 'nullable|string',
                'merchant_id' => 'required|exists:merchants,id',
                'data_activity_id' => 'required|exists:data_activity,id',
                'instruktur' => 'required|string',
            ]);
    
            $template = Sertifikat::findOrFail($id);
            $generatedPDFs = [];
    
            foreach ($validated['recipients'] as $recipient) {
                setlocale(LC_TIME, 'id_ID');
                Carbon::setLocale('id');
                $dateText = Carbon::parse($recipient['date'])->translatedFormat('d F Y');
                
                $certificateNumber = $this->generateCertificateNumber($template, $validated['certificate_number_format'] ?? null);
    
                $replacements = [
                    '{NAMA}' => $recipient['recipient_name'],
                    '{NOMOR}' => $certificateNumber,
                    '{TANGGAL}' => $dateText,
                    '{INSTRUKTUR}' => $validated['instruktur']
                ];
    
                $elements = $this->prepareElements($template->elements, $replacements);
    
                $filename = sprintf(
                    'sertifikat_%s_%s.pdf',
                    Str::slug($recipient['recipient_name']),
                    now()->format('YmdHis')
                );
                
                $downloadToken = Str::random(40);
    
                $data = [
                    'elements' => $elements,
                    'background_image' => Storage::disk('public')->path($template->background_image),
                ];
    
                $pdf = PDF::loadView('sertifikat.template', $data)->setPaper('a4', 'landscape');
                
                Storage::disk('public')->put('certificates/generated/' . $filename, $pdf->output());
    
                $user = User::where('email', $recipient['email'])->first();
    
                $download = CertificateDownload::create([
                    'sertifikat_id' => $template->id,
                    'token' => $downloadToken,
                    'filename' => $filename,
                    'recipient_name' => $recipient['recipient_name'],
                    'certificate_number' => $certificateNumber,
                    'user_id' => $user->id ?? null,
                    'expires_at' => now()->addDays(30),
                    'merchant_id' => $validated['merchant_id'],
                    'data_activity_id' => $validated['data_activity_id'],
                ]);

                if ($user) {
                    UserCertificate::create([
                        'user_id' => $user->id,
                        'data_activity_id' => $validated['data_activity_id'],
                        'certificate_download_id' => $download->id,
                        'assigned_at' => now(),
                        'status' => 'active',
                        'merchant_id' => $validated['merchant_id'],
                    ]);
                }
    
                // Email sending logic
                // ...
    
                $generatedPDFs[] = [
                    'recipient_name' => $recipient['recipient_name'],
                    'certificate_number' => $certificateNumber,
                    'download_url' => url('/sertifikat-templates/download/' . $downloadToken),
                ];
            }
    
            return response()->json([
                'status' => 'success',
                'message' => count($generatedPDFs) . ' sertifikat berhasil dibuat.',
                'data' => $generatedPDFs
            ]);
    
        } catch (\Exception $e) {
            Log::error('Error generating bulk PDFs: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function getUserCertificates($id)
    {
        try {
            // Check if user exists
            $user = User::find($id);
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            // Get certificates from UserCertificate with the download tokens
            $certificates = UserCertificate::with('certificateDownload')
                ->where('user_id', $id)
                ->where('status', 'active')
                ->get()
                ->map(function ($userCertificate) {
                    $download = $userCertificate->certificateDownload;
                    if (!$download) {
                        return null;
                    }
                    return [
                        'id' => $userCertificate->id,
                        'certificate_id' => $userCertificate->sertifikat_id,
                        'recipient_name' => $download->recipient_name,
                        'certificate_number' => $download->certificate_number,
                        'view_url' => '/sertifikat-templates/preview/' . $download->token,
                        'download_url' => '/sertifikat-templates/download/' . $download->token,
                        'download_token' => $download->token,
                        'expires_at' => $download->expires_at,
                        'assigned_at' => $userCertificate->created_at,
                        'status' => $userCertificate->status,
                        'merchant_id' => $userCertificate->merchant_id
                    ];
                })
                ->filter() // Remove any null values
                ->values(); // Reset array keys

            return response()->json([
                'status' => 'success',
                'data' => $certificates
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching user certificates: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengambil data sertifikat: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getCertificateByNumber() 
    {
        try {
            $certificateNumber = request()->input('certificate_number');
            if (empty($certificateNumber)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Nomor sertifikat tidak boleh kosong'
                ], 400);
            }

            // Find the certificate download by number
            $certificateDownload = CertificateDownload::where('certificate_number', $certificateNumber)->first();
            if (!$certificateDownload) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sertifikat tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'activity_name' => $certificateDownload->dataActivity->activity_name,
                    'date' => $certificateDownload->dataActivity->date,
                    'certificate_number' => $certificateDownload->certificate_number,
                    'recipient_name' => $certificateDownload->recipient_name,
                    'instruktur_name' => $certificateDownload->dataActivity->instruktur->name,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching certificate by number: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
