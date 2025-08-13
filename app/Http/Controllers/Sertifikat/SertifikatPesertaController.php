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

class SertifikatPesertaController extends Controller
{
    private $pdfWidth = 842;    // A4 Landscape width
    private $pdfHeight = 595;   // A4 Landscape height


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



    private function prepareElements($elements, $replacements)
    {
        return array_map(function($element) use ($replacements) {
            if ($element['type'] === 'text' && isset($element['placeholderType']) && $element['placeholderType'] !== 'custom') {
                $element['text'] = strtr($element['text'], $replacements);
            }
            return $element;
        }, $elements);
    }

    private function generateCertificateNumber($template, $format = null) 
    {
        try {
            // Use template's format if no format provided
            $format = $format ?? $template->certificate_number_format;
            
            if (empty($format)) {
                throw new \Exception('Format nomor sertifikat belum diatur');
            }

            // Find all groups of X's in the format
            preg_match_all('/X+/', $format, $matches, PREG_OFFSET_CAPTURE);
            
            if (empty($matches[0])) {
                throw new \Exception('Format harus mengandung minimal satu X sebagai placeholder nomor');
            }

            // Get the first sequence of X's as the increment placeholder
            $placeholder = $matches[0][0][0];
            $placeholderLength = strlen($placeholder);

            // Create a pattern that matches only the first X sequence
            $pattern = '/^' . str_repeat('X', $placeholderLength) . '/';
            
            // Get next number from template
            $nextNumber = $template->last_certificate_number + 1;
            
            // Update the last number in template
            $template->update([
                'last_certificate_number' => $nextNumber
            ]);

            // Format the number with leading zeros based on first X sequence length
            $formattedNumber = str_pad($nextNumber, $placeholderLength, '0', STR_PAD_LEFT);
            
            // Replace only the first sequence of X's with the number
            $certificateNumber = preg_replace($pattern, $formattedNumber, $format, 1);

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
                'certificate_number_format' => 'nullable|string' // Format nomor bisa dari request atau template
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
                $certificateNumber = $this->generateCertificateNumber($template, $validated['certificate_number_format']);

                // Process template elements for each recipient
                $elements = $this->prepareElements($template->elements, [
                    '{NAMA}' => $recipient['recipient_name'],
                    '{NOMOR}' => $certificateNumber,
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

                // Send email with certificate
                try {
                    Mail::to($recipient['email'])->send(
                        new CertificateGenerated(
                            $recipient['recipient_name'],
                            $recipient['certificate_number'],
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
                    'certificate_number' => $recipient['certificate_number'],
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
            $user = \App\Models\User::find($id);
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak ditemukan'
                ], 404);
            }
            
            // Get certificates from UserCertificate with the download tokens
            $certificates = \App\Models\UserCertificate::with('certificateDownload')
                ->where('user_id', $id)
                ->where('status', 'active')
                ->get()
                ->map(function($userCertificate) {
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
                        'status' => $userCertificate->status
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
