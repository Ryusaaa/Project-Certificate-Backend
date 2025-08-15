<?php

namespace App\Http\Controllers\Sertifikat;

use App\Http\Controllers\Controller;
use App\Models\Sertifikat;
use App\Models\CertificateDownload;
use App\Models\UserCertificate;
use App\Models\User;
use App\Mail\CertificateGenerated;
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

            // Process template elements
            $elements = $this->prepareElements($template->elements, [
                '{NAMA}' => $validated['recipient_name'],
                '{NOMOR}' => $validated['certificate_number'],
                '{TANGGAL}' => $dateText,
                '{INSTRUKTUR}' => $template->instruktur ?? '',
                '{QRCODE}' => $this->getQRCodeFromCertificate($validated['certificate_number']) ?? ''
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

            // Process template elements
            $elements = $this->prepareElements($template->elements, [
                '{NAMA}' => $validated['recipient_name'],
                '{NOMOR}' => $validated['certificate_number'],
                '{TANGGAL}' => $dateText,
                '{INSTRUKTUR}' => $template->instruktur ?? '',
                '{QRCODE}' => $this->getQRCodeFromCertificate($validated['certificate_number']) ?? ''
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



    private function prepareElements($elements, $replacements)
    {
        return array_map(function($element) use ($replacements) {
            if (isset($element['placeholderType']) && $element['placeholderType'] !== 'custom') {
                if ($element['type'] === 'text') {
                    $element['text'] = strtr($element['text'], $replacements);
                } elseif ($element['type'] === 'qrcode' && isset($replacements['{QRCODE}'])) {
                    $element['text'] = $replacements['{QRCODE}'];
                }
            }
            return $element;
        }, $elements);
    }

    private function getQRCodeFromCertificate($certificateNumber)
    {
        try {
            Log::info('Starting QR code generation for certificate number: ' . $certificateNumber);
            
            // Debug: Output detailed info about the search
            Log::info('Certificate search details:', [
                'searched_number' => $certificateNumber,
                'length' => strlen($certificateNumber),
                'raw_bytes' => bin2hex($certificateNumber),
                'special_chars' => preg_replace('/[^\/\-_]/', '', $certificateNumber)
            ]);
            
            // Debug: Log all certificate numbers for comparison
            $allCerts = CertificateDownload::select('certificate_number')->get();
            Log::info('Available certificates:', $allCerts->map(function($cert) {
                return [
                    'number' => $cert->certificate_number,
                    'length' => strlen($cert->certificate_number),
                    'raw_bytes' => bin2hex($cert->certificate_number)
                ];
            })->toArray());
            
            // Try exact match first (PostgreSQL)
            $certificateDownload = CertificateDownload::where('certificate_number', $certificateNumber)->first();
            
            // If not found, try with cleaned string (remove any invisible characters)
            if (!$certificateDownload) {
                Log::info('Trying with cleaned string...');
                $cleanNumber = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $certificateNumber);
                $certificateDownload = CertificateDownload::where('certificate_number', $cleanNumber)->first();
            }
            
            // If still not found, try case-insensitive match (PostgreSQL)
            if (!$certificateDownload) {
                Log::info('Trying case-insensitive match...');
                $certificateDownload = CertificateDownload::whereRaw('certificate_number ILIKE ?', [$certificateNumber])->first();
            }
            
            // If still not found, try with trimmed values
            if (!$certificateDownload) {
                Log::info('Trying with trimmed values...');
                $trimmedNumber = trim($certificateNumber);
                $certificateDownload = CertificateDownload::whereRaw('TRIM(certificate_number) = ?', [$trimmedNumber])->first();
            }

            if (!$certificateDownload) {
                // Log all certificates for comparison
                $existingCerts = CertificateDownload::get();
                Log::error('Certificate comparison:', [
                    'searched_for' => [
                        'value' => $certificateNumber,
                        'length' => strlen($certificateNumber),
                        'bytes' => bin2hex($certificateNumber),
                        'cleaned' => $cleanNumber
                    ],
                    'available_certs' => $existingCerts->map(function($cert) {
                        return [
                            'value' => $cert->certificate_number,
                            'length' => strlen($cert->certificate_number),
                            'bytes' => bin2hex($cert->certificate_number)
                        ];
                    })
                ]);
                Log::error('Certificate download not found for number: ' . $certificateNumber);
                return '';
            }
            
            Log::info('Found CertificateDownload:', [
                'id' => $certificateDownload->id,
                'token' => $certificateDownload->token,
                'certificate_number' => $certificateDownload->certificate_number,
                'search_term' => $certificateNumber
            ]);

            // Generate QR code filename based on certificate token
            $qrCodeFileName = 'qrcodes/' . $certificateDownload->token . '.svg';
            
            // Check if QR code already exists in storage
            if (Storage::disk('public')->exists($qrCodeFileName)) {
                Log::info('Found existing QR code file: ' . $qrCodeFileName);
                $existingQr = Storage::disk('public')->get($qrCodeFileName);
                Log::info('Existing QR code loaded, length: ' . strlen($existingQr));
                return $existingQr;
            }
            
            Log::info('No existing QR code found, will generate new one');

            // Generate QR Code with token
            $qrCodeContent = env('FRONTEND_URL') . '/sertifikat-templates/download/' . $certificateDownload->token;
            Log::info('Generating QR code with URL: ' . $qrCodeContent);
            
            $qrCodeSvg = QrCode::size(200)
                ->format('svg')
                ->generate($qrCodeContent);
            
            Log::info('QR code generated, SVG length: ' . strlen($qrCodeSvg));
            
            // Save generated QR code
            $qrCodeFileName = 'qrcodes/' . $certificateDownload->token . '.svg';
            Log::info('Saving QR code to: ' . $qrCodeFileName);
            
            // Ensure directory exists
            Storage::disk('public')->makeDirectory('qrcodes');
            Log::info('QR code directory created/verified');
            
            // Save QR code
            $saved = Storage::disk('public')->put($qrCodeFileName, $qrCodeSvg);
            Log::info('QR code saved to storage: ' . ($saved ? 'Success' : 'Failed'));
            
            // Update UserCertificate if exists
            $userCertificate = UserCertificate::where('certificate_download_id', $certificateDownload->id)->first();
            if ($userCertificate && $saved) {
                $userCertificate->update([
                    'qrcode_path' => $qrCodeFileName
                ]);
                Log::info('UserCertificate updated with QR code path');
            }
            
            // Verify QR code content
            if (empty($qrCodeSvg)) {
                Log::error('Generated QR code is empty');
                return '';
            }
            
            Log::info('Successfully returning QR code SVG');
            return $qrCodeSvg;
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
            // Validate request
            $validated = $request->validate([
                'recipients' => 'required|array|min:1',
                'recipients.*.recipient_name' => 'required|string',
                'recipients.*.date' => 'required|date',
                'recipients.*.email' => 'required|email',
                'certificate_number_format' => 'nullable|string',
                'merchant_id' => 'required',
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

            $generatedPDFs = [];

            foreach ($validated['recipients'] as $recipient) {
                // Format date for each recipient
                setlocale(LC_TIME, 'id_ID');
                Carbon::setLocale('id');
                $dateText = Carbon::parse($recipient['date'])->translatedFormat('d F Y');

                // Generate certificate number
                $certificateNumber = $this->generateCertificateNumber($template, $validated['certificate_number_format'] ?? null);

                // Generate unique filename and token for each recipient
                $filename = sprintf(
                    'sertifikat_%s_%s_%s.pdf',
                    Str::slug($recipient['recipient_name']),
                    Str::slug($certificateNumber),
                    now()->format('Ymd_His')
                );
                
                $downloadToken = Str::random(12); // Generate secure token

                // Create download record first
                $download = $template->createDownload([
                    'token' => $downloadToken,
                    'filename' => $filename,
                    'recipient_name' => $recipient['recipient_name'],
                    'certificate_number' => $certificateNumber,
                    'user_id' => $request->user() ? $request->user()->id : null,
                    'expires_at' => now()->addDays(30) // Token berlaku 30 hari
                ]);

                // Find or create user certificate
                $user = User::where('email', $recipient['email'])->first();
                $userCertificate = null;
                if ($user) {
                    Log::info('Creating UserCertificate for user:', [
                        'user_id' => $user->id,
                        'download_id' => $download->id,
                        'merchant_id' => $validated['merchant_id']
                    ]);

                    $userCertificate = UserCertificate::create([
                        'user_id' => $user->id,
                        'certificate_download_id' => $download->id,
                        'assigned_at' => now(),
                        'status' => 'active',
                        'merchant_id' => $validated['merchant_id']
                    ]);

                    if (!$userCertificate) {
                        Log::error('Failed to create UserCertificate');
                    } else {
                        Log::info('UserCertificate created successfully with ID: ' . $userCertificate->id);
                    }
                } else {
                    Log::warning('User not found for email: ' . $recipient['email']);
                }

                // Refresh to ensure we have the latest data
                if ($userCertificate) {
                    $userCertificate->refresh();
                }

                // Now process template elements after certificate record exists
                $elements = $this->prepareElements($template->elements, [
                    '{NAMA}' => $recipient['recipient_name'],
                    '{NOMOR}' => $certificateNumber,
                    '{TANGGAL}' => $dateText,
                    '{INSTRUKTUR}' => $template->instruktur ?? '',
                    '{QRCODE}' => $this->getQRCodeFromCertificate($certificateNumber) ?? ''
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

                // Save to storage
                $pdfPath = 'certificates/generated/' . $filename;
                Storage::disk('public')->put($pdfPath, $pdf->output());

                // Send email with certificate
                try {
                    Mail::to($recipient['email'])->send(
                        new CertificateGenerated(
                            $recipient['recipient_name'],
                            $certificateNumber,
                            '/sertifikat-templates/download/' . $downloadToken,
                            $pdf->output()
                        )
                    );
                } catch (\Exception $e) {
                    Log::error('Failed to send certificate email', [
                        'recipient' => $recipient['email'],
                        'error' => $e->getMessage()
                    ]);
                }

                // Add to generated PDFs array
                $generatedPDFs[] = [
                    'recipient_name' => $recipient['recipient_name'],
                    'certificate_number' => $certificateNumber,
                    'view_url' => '/storage/' . $pdfPath,
                    'download_url' => '/sertifikat-templates/download/' . $downloadToken,
                    'download_token' => $downloadToken,
                    'email_sent' => true
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
}
