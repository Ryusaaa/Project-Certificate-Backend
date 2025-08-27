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
                'instruktur' => 'nullable|string',
                'elements' => 'sometimes|array'
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

            // Prefer elements coming from the editor payload (if provided), otherwise use stored template elements
            $templateElements = [];
            if ($request->has('elements') && is_array($request->input('elements'))) {
                $templateElements = $request->input('elements');
            } else {
                $templateElements = is_array($template->elements) ? $template->elements : [];
            }
            
            // Process template elements
            $elements = $this->prepareElements($templateElements, [
                '{NAMA}' => $validated['recipient_name'],
                '{NOMOR}' => $validated['certificate_number'],
                '{TANGGAL}' => $dateText,
                '{INSTRUKTUR}' => $validated['instruktur'] ?? ''
            ]);

            // Prepare PDF data
            $data = [
                'template' => $template,
                'elements' => $elements,
                'background_image' => Storage::disk('public')->path($template->background_image),
                'pageWidth' => $this->pdfWidth,
                'pageHeight' => $this->pdfHeight
            ];

            // Configure PDF generation
            $pdf = PDF::loadView('sertifikat.template', $data)
                ->setPaper([0, 0, $this->pdfWidth, $this->pdfHeight], 'landscape')
                ->setOption('enable-local-file-access', true)
                ->setOption('isHtml5ParserEnabled', true);

            // Generate temporary filename for preview
            $filename = sprintf(
                'preview_sertifikat_%s_%s.pdf',
                Str::slug($validated['recipient_name']),
                now()->format('Ymd_His')
            );

            // Save to temporary storage
            $pdfPath = 'certificates/previews/' . $filename;
            Storage::disk('public')->put($pdfPath, $pdf->output());
            $previewUrl = Storage::url($pdfPath);

            return response()->json([
                'status' => 'success',
                'message' => 'Preview berhasil dibuat',
                'data' => ['preview_url' => $previewUrl]
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating preview PDF: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
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
                'instruktur' => 'required|string',
            ]);

            $template = Sertifikat::find($id);
            if (!$template) {
                return response()->json(['status' => 'error', 'message' => 'Template tidak ditemukan'], 404);
            }

            setlocale(LC_TIME, 'id_ID');
            Carbon::setLocale('id');
            $dateText = Carbon::parse($validated['date'])->translatedFormat('d F Y');
            
            $templateElements = $request->has('elements') && is_array($request->input('elements'))
                ? $request->input('elements')
                : (is_array($template->elements) ? $template->elements : []);

            $elements = $this->prepareElements($templateElements, [
                '{NAMA}' => $validated['recipient_name'],
                '{NOMOR}' => $validated['certificate_number'],
                '{TANGGAL}' => $dateText,
                '{INSTRUKTUR}' => $validated['instruktur']
            ]);

            $data = [
                'template' => $template,
                'elements' => $elements,
                'background_image' => Storage::disk('public')->path($template->background_image),
                'pageWidth' => $this->pdfWidth,
                'pageHeight' => $this->pdfHeight
            ];

            $pdf = PDF::loadView('sertifikat.template', $data)
                ->setPaper([0, 0, $this->pdfWidth, $this->pdfHeight], 'landscape');

            $filename = sprintf('sertifikat_%s.pdf', Str::slug($validated['recipient_name']));
            return $pdf->download($filename);

        } catch (\Exception $e) {
            Log::error('Error generating PDF: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    private function prepareElements($elements, $replacements)
    {
        if (!is_array($elements)) {
            return [];
        }
        
        return array_map(function($element) use ($replacements) {
            if ($element['type'] === 'text' && isset($element['text'])) {
                if (isset($element['placeholderType']) && $element['placeholderType'] !== 'custom') {
                    if (isset($replacements[$element['text']])) {
                        $element['text'] = $replacements[$element['text']];
                    }
                }
            }
            
            if ($element['type'] === 'qrcode') {
                $certificateNumber = $element['content'] ?? $replacements['{NOMOR}'] ?? '';
                if ($certificateNumber) {
                    $element['qrcode'] = $this->getQRCodeFromCertificate($certificateNumber);
                }
            }

            return $element;
        }, $elements);
    }

    public function getQRCodeFromCertificate($certificateNumber)
    {
        try {
            if (empty($certificateNumber)) return '';

            $certificateDownload = CertificateDownload::where('certificate_number', $certificateNumber)->first();
            if (!$certificateDownload) {
                Log::warning('Certificate download not found for QR generation', ['number' => $certificateNumber]);
                return ''; 
            }
            
            $qrCodeFileName = 'qrcodes/' . $certificateDownload->token . '.png';
            if (Storage::disk('public')->exists($qrCodeFileName)) {
                return 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($qrCodeFileName));
            }

            $qrCodeContent = config('app.frontend_url') . '/peserta?certificate_number=' . urlencode($certificateNumber);

            $qrCode = QrCode::format('png')
                ->size(300)->margin(1)->errorCorrection('H')
                ->color(0, 0, 0)->backgroundColor(255, 255, 255, 127) // semi-transparent
                ->generate($qrCodeContent);

            Storage::disk('public')->put($qrCodeFileName, $qrCode);
            return 'data:image/png;base64,' . base64_encode($qrCode);

        } catch (\Exception $e) {
            Log::error('Error generating QR code', ['error' => $e->getMessage()]);
            return '';
        }
    }
            
    private function generateCertificateNumber($template, $format = null)
    {
        try {
            $format = $format ?? $template->certificate_number_format;
            if (empty($format)) throw new \Exception('Format nomor sertifikat belum diatur');

            preg_match_all('/(?:^|[\/\-_])([X]+)(?=[\/\-_]|$)/', $format, $matches, PREG_OFFSET_CAPTURE);
            if (empty($matches[1])) throw new \Exception('Format harus mengandung placeholder X');

            $placeholder = $matches[1][0][0];
            $placeholderPosition = $matches[1][0][1];
            $placeholderLength = strlen($placeholder);
            
            $nextNumber = $template->last_certificate_number + 1;
            $template->update(['last_certificate_number' => $nextNumber]);
            $formattedNumber = str_pad($nextNumber, $placeholderLength, '0', STR_PAD_LEFT);
            
            return substr_replace($format, $formattedNumber, $placeholderPosition, $placeholderLength);
        } catch (\Exception $e) {
            Log::error('Error generating certificate number: ' . $e->getMessage());
            throw $e;
        }
    }

    public function downloadPDF($token)
    {
        try {
            $download = CertificateDownload::where('token', $token)->firstOrFail();
            if ($download->isExpired()) return response()->json(['message' => 'Link download kadaluarsa'], 410);
            
            $filepath = 'certificates/generated/' . $download->filename;
            if (!Storage::disk('public')->exists($filepath)) return response()->json(['message' => 'File tidak ditemukan'], 404);

            $download->incrementDownloadCount();
            return response()->download(Storage::disk('public')->path($filepath));
        } catch (\Exception $e) {
            Log::error('Error downloading PDF', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal mengunduh file'], 500);
        }
    }

    public function previewPDFWithToken($token)
    {
        try {
            $download = CertificateDownload::where('token', $token)->firstOrFail();
            if ($download->isExpired()) return response()->json(['message' => 'Link preview kadaluarsa'], 410);

            $filepath = 'certificates/generated/' . $download->filename;
            if (!Storage::disk('public')->exists($filepath)) return response()->json(['message' => 'File tidak ditemukan'], 404);

            return response()->file(Storage::disk('public')->path($filepath));
        } catch (\Exception $e) {
            Log::error('Error previewing PDF', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Gagal menampilkan file'], 500);
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
                
                $templateElements = $request->has('elements') && is_array($request->input('elements'))
                    ? $request->input('elements')
                    : (is_array($template->elements) ? $template->elements : []);
                
                $certificateNumber = $this->generateCertificateNumber($template, $validated['certificate_number_format'] ?? null);
                $filename = sprintf('sertifikat_%s_%s.pdf', Str::slug($recipient['recipient_name']), Str::slug($certificateNumber));
                $downloadToken = Str::random(12);

                $user = User::where('email', $recipient['email'])->first();
                $download = $template->createDownload([
                    'token' => $downloadToken, 'filename' => $filename,
                    'recipient_name' => $recipient['recipient_name'], 'certificate_number' => $certificateNumber,
                    'user_id' => $user->id ?? null, 'expires_at' => now()->addDays(30),
                    'merchant_id' => $validated['merchant_id'], 'data_activity_id' => $validated['data_activity_id'],
                    'sertifikat_id' => $id
                ]);

                $elements = $this->prepareElements($templateElements, [
                    '{NAMA}' => $recipient['recipient_name'], '{NOMOR}' => $certificateNumber,
                    '{TANGGAL}' => $dateText, '{INSTRUKTUR}' => $validated['instruktur']
                ]);

                $data = [
                    'template' => $template, 'elements' => $elements,
                    'background_image' => Storage::disk('public')->path($template->background_image),
                    'pageWidth' => $this->pdfWidth, 'pageHeight' => $this->pdfHeight
                ];

                $pdf = PDF::loadView('sertifikat.template', $data)
                    ->setPaper([0, 0, $this->pdfWidth, $this->pdfHeight], 'landscape');

                $pdfPath = 'certificates/generated/' . $filename;
                Storage::disk('public')->put($pdfPath, $pdf->output());

                if ($user) {
                    UserCertificate::create([
                        'user_id' => $user->id, 'data_activity_id' => $validated['data_activity_id'],
                        'certificate_download_id' => $download->id, 'assigned_at' => now(),
                        'status' => 'active', 'merchant_id' => $validated['merchant_id'],
                        'qrcode_path' => 'qrcodes/' . $downloadToken . '.png'
                    ]);
                }

                try {
                    Mail::to($recipient['email'])->queue(new CertificateGenerated(
                        $recipient['recipient_name'], $certificateNumber,
                        url('/sertifikat-templates/download/' . $downloadToken),
                        $pdfPath, $filename
                    ));
                    $emailQueued = true;
                } catch (\Exception $e) {
                    Log::error('Failed to queue email', ['error' => $e->getMessage()]);
                    $emailQueued = false;
                }

                $generatedPDFs[] = [
                    'recipient_name' => $recipient['recipient_name'], 'certificate_number' => $certificateNumber,
                    'view_url' => Storage::url($pdfPath), 'download_url' => url('/sertifikat-templates/download/' . $downloadToken),
                    'email_queued' => $emailQueued
                ];
            }

            return response()->json(['status' => 'success', 'message' => 'Sertifikat berhasil dibuat', 'data' => $generatedPDFs]);
        } catch (\Exception $e) {
            Log::error('Error generating bulk PDFs', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Gagal membuat sertifikat massal.'], 500);
        }
    }

    public function getUserCertificates($id)
    {
        try {
            $user = User::findOrFail($id);
            $certificates = UserCertificate::with('certificateDownload.dataActivity')
                ->where('user_id', $id)->where('status', 'active')->get()
                ->map(function ($cert) {
                    if (!$cert->certificateDownload) return null;
                    return [
                        'id' => $cert->id,
                        'recipient_name' => $cert->certificateDownload->recipient_name,
                        'certificate_number' => $cert->certificateDownload->certificate_number,
                        'activity_name' => $cert->certificateDownload->dataActivity->activity_name ?? 'N/A',
                        'view_url' => url('/sertifikat-templates/preview/' . $cert->certificateDownload->token),
                        'download_url' => url('/sertifikat-templates/download/' . $cert->certificateDownload->token),
                    ];
                })->filter()->values();
            return response()->json(['status' => 'success', 'data' => $certificates]);
        } catch (\Exception $e) {
            Log::error('Error fetching user certificates', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Gagal mengambil data sertifikat.'], 500);
        }
    }

    public function getCertificateByNumber(Request $request) 
    {
        try {
            $number = $request->input('certificate_number');
            if (empty($number)) return response()->json(['message' => 'Nomor sertifikat diperlukan'], 400);

            $certDownload = CertificateDownload::with('dataActivity.instruktur')->where('certificate_number', $number)->firstOrFail();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'activity_name' => $certDownload->dataActivity->activity_name,
                    'date' => $certDownload->dataActivity->date,
                    'certificate_number' => $certDownload->certificate_number,
                    'recipient_name' => $certDownload->recipient_name,
                    'instruktur_name' => $certDownload->dataActivity->instruktur->name ?? 'N/A',
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching certificate by number', ['error' => $e->getMessage()]);
            return response()->json(['status' => 'error', 'message' => 'Sertifikat tidak ditemukan'], 404);
        }
    }
}
