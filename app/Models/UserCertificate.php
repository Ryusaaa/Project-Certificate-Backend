<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToMerchant;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\CertificateDownload;

class UserCertificate extends Model
{
    use BelongsToMerchant;
    protected $fillable = [
        'user_id',
        'certificate_download_id',
        'status',
        'assigned_at',
        'qrcode_path',
        'merchant_id'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($userCertificate) {
            Log::info('UserCertificate creating event fired.');

            // Get the token from CertificateDownload
            $certificateDownload = CertificateDownload::find($userCertificate->certificate_download_id);
            $token = $certificateDownload ? $certificateDownload->token : null;

            if (!$token) {
                Log::error('No token found for certificate_download_id: ' . $userCertificate->certificate_download_id);
                return;
            }

            // Generate QR Code with token
            $qrCodeContent = config('app.url') . '/sertifikat-templates/download/' . $token;
            Log::info('QR Code Content: ' . $qrCodeContent);

            $qrCodeFileName = 'qrcodes/' . $token . '.svg';
            Log::info('QR Code File Name: ' . $qrCodeFileName);

            try {
                // Ensure the directory exists
                Storage::disk('public')->makeDirectory('qrcodes');
                Log::info('QR Code directory ensured.');

                $qrCodeSvg = QrCode::size(200)->generate($qrCodeContent);
                Log::info('QR Code SVG generated (first 50 chars): ' . substr($qrCodeSvg, 0, 50));

                Storage::disk('public')->put($qrCodeFileName, $qrCodeSvg);
                Log::info('QR Code file saved to: ' . $qrCodeFileName);

                $userCertificate->qrcode_path = 'storage/' . $qrCodeFileName;
                Log::info('QR Code path set on model: ' . $userCertificate->qrcode_path);
            } catch (\Exception $e) {
                Log::error('Error generating or saving QR Code: ' . $e->getMessage());
            }
        });
    }

    /**
     * Get the user that owns this certificate.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the certificate download record.
     */
    public function certificateDownload()
    {
        return $this->belongsTo(CertificateDownload::class, 'certificate_download_id');
    }

    /**
     * Check if the certificate is active.
     */
    public function isActive()
    {
        return $this->status === 'active';
    }
}
