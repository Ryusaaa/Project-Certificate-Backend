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


    public function previewPDF(Request $request, $id)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'recipient_name' => 'required|string',
                'certificate_number' => 'required|string',
                'date' => 'required|date',
                'merchant_id' => 'required|exists:merchants,id',
                'instruktur' => 'nullable|string'
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

            // Get QR code SVG
            $qrCodeSvg = $this->getQRCodeFromCertificate($validated['certificate_number']);
            Log::info('QR code SVG retrieved:', [
                'has_content' => !empty($qrCodeSvg),
                'content_length' => strlen($qrCodeSvg ?? '')
            ]);

            // Get template elements and add QR code element
            $templateElements = is_array($template->elements) ? $template->elements : [];
            
            // Remove any existing QR code elements
            $templateElements = array_filter($templateElements, function($el) {
                return $el['type'] !== 'qrcode';
            });
            
            // Add new QR code element
            $qrCode = $this->getQRCodeFromCertificate($validated['certificate_number']);
            $templateElements[] = [
                'type' => 'qrcode',
                'x' => 7,
                'y' => 23,
                'width' => 100,
                'height' => 100,
                'content' => $validated['certificate_number'],
                'qrcode' => $qrCode,
                'placeholderType' => 'qrcode'
            ];
            
            // Process template elements
            $elements = $this->prepareElements($templateElements, [
                '{NAMA}' => $validated['recipient_name'],
                '{NOMOR}' => $validated['certificate_number'],
                '{TANGGAL}' => $dateText,
                '{INSTRUKTUR}' => $validated['instruktur'] ?? ''  // Menggunakan instruktur dari request
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
            dispatch(function () use ($pdfPath) {
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
            
            // Process template elements
            $templateElements = is_array($template->elements) ? $template->elements : [];
            
            // Remove any existing QR code elements
            $templateElements = array_filter($templateElements, function($el) {
                return $el['type'] !== 'qrcode';
            });
            
            // Add new QR code element
            $qrCode = $this->getQRCodeFromCertificate($validated['certificate_number']);
            $templateElements[] = [
                'type' => 'qrcode',
                'x' => 7,
                'y' => 23,
                'width' => 100,
                'height' => 100,
                'content' => $validated['certificate_number'],
                'qrcode' => $qrCode,
                'placeholderType' => 'qrcode'
            ];
            
            // Process template elements with replacements
            $elements = $this->prepareElements($templateElements, [
                '{NAMA}' => $validated['recipient_name'],
                '{NOMOR}' => $validated['certificate_number'],
                '{TANGGAL}' => $dateText,
                '{INSTRUKTUR}' => $validated['instruktur']
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

    private function prepareElements($elements, $replacements)
    {
        Log::info('Preparing elements with replacements', [
            'elements_count' => count($elements),
            'replacements' => array_keys($replacements)
        ]);
        
        return array_map(function($element) use ($replacements) {
            if ($element['type'] === 'qrcode') {
                Log::info('Processing QR code element', ['element' => $element]);
                
                // First try to get certificate number from content
                $certificateNumber = null;
                if (!empty($element['content'])) {
                    $certificateNumber = $element['content'];
                } elseif (isset($replacements['{NOMOR}'])) {
                    $certificateNumber = $replacements['{NOMOR}'];
                }
                
                // Generate QR code if we have a certificate number
                if ($certificateNumber) {
                    $element['qrcode'] = $this->getQRCodeFromCertificate($certificateNumber);
                    Log::info('Generated QR code for certificate number', [
                        'certificate_number' => $certificateNumber,
                        'has_qr' => !empty($element['qrcode'])
                    ]);
                }
                
                Log::debug('QR code in element', [
                    'has_content' => !empty($element['qrcode']),
                    'content_length' => strlen($element['qrcode'] ?? ''),
                    'certificate_number' => $certificateNumber,
                    'position' => isset($element['x']) && isset($element['y']) ? $element['x'] . 'pt, ' . $element['y'] . 'pt' : 'unknown'
                ]);
            }
            elseif (isset($element['placeholderType']) && $element['placeholderType'] !== 'custom') {
                if (isset($replacements[$element['text']])) {
                    $element['text'] = $replacements[$element['text']];
                }
            }
            return $element;
        }, $elements);
    }

    public function getQRCodeFromCertificate($certificateNumber)
    {
        try {
            if (empty($certificateNumber)) {
                Log::warning('Empty certificate number provided');
                return '';
            }

            Log::info('Starting QR code generation for certificate number: ' . $certificateNumber);
            
            // Debug: Output detailed info about the search
            Log::info('Certificate search details:', [
                'searched_number' => $certificateNumber,
                'length' => strlen($certificateNumber),
                'raw_bytes' => bin2hex($certificateNumber),
                'special_chars' => preg_replace('/[^\/\-_]/', '', $certificateNumber)
            ]);
            
            // Normalize and try lookup using normalized logic
            $normalized = CertificateDownload::normalizeCertificateNumber($certificateNumber);
            Log::info('Normalized certificate number for lookup: ' . $normalized);

            $certificateDownload = null;
            if ($normalized) {
                // Try exact normalized match, then case-insensitive via scope
                $certificateDownload = CertificateDownload::where('certificate_number', $normalized)->first();
                if (!$certificateDownload) {
                    Log::info('Trying case-insensitive normalized match...');
                    $certificateDownload = CertificateDownload::whereRaw('certificate_number ILIKE ?', [$normalized])->first();
                }
            }

            if (!$certificateDownload) {
                Log::error('Certificate download not found for number: ' . $certificateNumber);
                return '';
            }
            
            Log::info('Found CertificateDownload:', [
                'id' => $certificateDownload->id,
                'token' => $certificateDownload->token,
                'certificate_number' => $certificateDownload->certificate_number,
                'search_term' => $certificateNumber
            ]);

            // Check if QR code already exists
            $qrCodeFileName = 'qrcodes/' . $certificateDownload->token . '.png';
            if (Storage::disk('public')->exists($qrCodeFileName)) {
                Log::info('Found existing QR code file: ' . $qrCodeFileName);
                $pngData = Storage::disk('public')->get($qrCodeFileName);
                return 'data:image/png;base64,' . base64_encode($pngData);
            }

            // Generate new QR code
            Log::info('Generating new QR code');
            $qrCodeContent = env('FRONTEND_URL') . '/sertifikat-templates/download/' . $certificateDownload->token;
            
            // Generate QR code
            $qrCode = QrCode::format('png')
                ->size(400)
                ->margin(1)
                ->errorCorrection('H')
                ->color(0, 0, 0)
                ->backgroundColor(255, 255, 255);

            // Generate PNG data
            $pngData = $qrCode->generate($qrCodeContent);
            if (empty($pngData)) {
                throw new \Exception("Failed to generate QR code image");
            }

            // Save the QR code
            Storage::disk('public')->makeDirectory('qrcodes');
            Storage::disk('public')->put($qrCodeFileName, $pngData);

            // Update UserCertificate if exists
            $userCertificate = UserCertificate::where('certificate_download_id', $certificateDownload->id)->first();
            if ($userCertificate) {
                $userCertificate->update(['qrcode_path' => $qrCodeFileName]);
            }

            // Return the QR code as base64 data URI
            $qrCodeImage = 'data:image/png;base64,' . base64_encode($pngData);
            Log::info('Successfully generated and saved QR code');
            return $qrCodeImage;

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
            // Validate request with instruktur field
            $validated = $request->validate([
                'recipients' => 'required|array|min:1',
                'recipients.*.recipient_name' => 'required|string',
                'recipients.*.date' => 'required|date',
                'recipients.*.email' => 'required|email',
                'certificate_number_format' => 'nullable|string',
                'merchant_id' => 'required',
                'data_activity_id' => 'required|exists:data_activity,id',
                'instruktur' => 'required|string',
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

                setlocale(LC_TIME, 'id_ID');
                Carbon::setLocale('id');
                $dateText = Carbon::parse($recipient['date'])->translatedFormat('d F Y');
                
                // Process template elements with replacements
                $templateElements = is_array($template->elements) ? $template->elements : [];
                
                // Remove any existing QR code elements
                $templateElements = array_filter($templateElements, function($el) {
                    return $el['type'] !== 'qrcode';
                });
                
                // Generate QR code for this certificate
                $certificateNumber = $this->generateCertificateNumber($template, $validated['certificate_number_format'] ?? null);
                $qrCode = $this->getQRCodeFromCertificate($certificateNumber);
                
                // Add QR code element
                $templateElements[] = [
                    'type' => 'qrcode',
                    'x' => 7,
                    'y' => 23,
                    'width' => 100,
                    'height' => 100,
                    'content' => $certificateNumber,
                    'qrcode' => $qrCode,
                    'placeholderType' => 'qrcode'
                ];
                
                // Process elements with replacements including instruktur
                $elements = $this->prepareElements($templateElements, [
                    '{NAMA}' => $recipient['recipient_name'],
                    '{NOMOR}' => $certificateNumber,
                    '{TANGGAL}' => $dateText,
                    '{INSTRUKTUR}' => $validated['instruktur']
                ]);

                // Generate unique filename and token for each recipient
                $filename = sprintf(
                    'sertifikat_%s_%s_%s.pdf',
                    Str::slug($recipient['recipient_name']),
                    Str::slug($certificateNumber),
                    now()->format('Ymd_His')
                );
                
                $downloadToken = Str::random(12);

                // Prepare PDF data with processed elements
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

                // Save PDF to storage
                Storage::disk('public')->makeDirectory('certificates/generated');
                Storage::disk('public')->put('certificates/generated/' . $filename, $pdf->output());

                // Create download record
                $user = User::where('email', $recipient['email'])->first();
                $download = $template->createDownload([
                    'token' => $downloadToken,
                    'filename' => $filename,
                    'recipient_name' => $recipient['recipient_name'],
                    'certificate_number' => $certificateNumber,
                    'user_id' => $user ? $user->id : null,
                    'expires_at' => now()->addDays(30),
                    'merchant_id' => $validated['merchant_id'], // pastikan ini ada
                    'data_activity_id' => $validated['data_activity_id'], // pastikan ini ada
                    'sertifikat_id' => $id
                ]);

                // Debug: Log created download
                    Log::info('Certificate Download Created:', [
                        'id' => $download->id,
                        'merchant_id' => $download->merchant_id,
                        'data_activity_id' => $download->data_activity_id,
                        'certificate_number' => $download->certificate_number,
                        'certificate_number_hex' => bin2hex($download->certificate_number)
                    ]);

                // Create UserCertificate if user exists
                $userCertificate = null;
                if ($user) {
                    Log::info('Creating UserCertificate for user:', [
                        'user_id' => $user->id,
                        'download_id' => $download->id,
                        'merchant_id' => $validated['merchant_id'],
                        'data_activity_id' => $validated['data_activity_id']
                    ]);

                    $userCertificate = UserCertificate::create([
                        'user_id' => $user->id,
                        'data_activity_id' => $validated['data_activity_id'], // Use validated data directly
                        'certificate_download_id' => $download->id,
                        'assigned_at' => now(),
                        'status' => 'active',
                        'merchant_id' => $validated['merchant_id'], // Use validated data directly
                        'qrcode_path' => 'qrcodes/' . $downloadToken . '.png'
                    ]);

                    if (!$userCertificate) {
                        Log::error('Failed to create UserCertificate');
                    } else {
                        Log::info('UserCertificate created successfully:', $userCertificate->toArray());
                    }
                } else {
                    Log::warning('User not found for email: ' . $recipient['email']);
                }


                // Save PDF to storage (already saved earlier in loop for bulk generation,
                // but ensure the file exists and path is available for the queued job)
                $pdfPath = 'certificates/generated/' . $filename;
                if (!Storage::disk('public')->exists($pdfPath)) {
                    Storage::disk('public')->put($pdfPath, $pdf->output());
                }

                // Kirim email via queue menggunakan Laravel Mailable dengan path (bukan raw bytes)
                $emailQueued = false;
                try {
                    Mail::to($recipient['email'])->queue(new \App\Mail\CertificateGenerated(
                        $recipient['recipient_name'],
                        $certificateNumber,
                        url('/sertifikat-templates/download/' . $downloadToken),
                        $pdfPath,
                        $filename
                    ));
                    $emailQueued = true;
                } catch (\Exception $e) {
                    Log::error('Failed to queue certificate email', [
                        'recipient' => $recipient['email'],
                        'error' => $e->getMessage()
                    ]);
                }

                $generatedPDFs[] = [
                    'recipient_name' => $recipient['recipient_name'],
                    'certificate_number' => $certificateNumber,
                    'view_url' => '/storage/certificates/generated/' . $filename,
                    'download_url' => '/sertifikat-templates/download/' . $downloadToken,
                    'download_token' => $downloadToken,
                    'email_queued' => $emailQueued
                ];
            }

            return response()->json([
                'status' => 'success',
                'message' => count($generatedPDFs) . ' sertifikat berhasil dibuat',
                'data' => $generatedPDFs
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating bulk PDFs: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
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
