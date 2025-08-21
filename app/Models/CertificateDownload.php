<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToMerchant;

class CertificateDownload extends Model
{
    use BelongsToMerchant;

    protected $table = 'certificate_downloads';
    protected $fillable = [
        'sertifikat_id',
        'token',
        'filename',
        'recipient_name',
        'certificate_number',
        'user_id',
        'expires_at',
        'download_count',
        'merchant_id',
        'data_activity_id'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'download_count' => 'integer'
    ];

    /**
     * Get the certificate template that this download belongs to.
     */
    public function sertifikat()
    {
        return $this->belongsTo(Sertifikat::class);
    }

    /**
     * Get the user who generated this certificate (if any).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the download token has expired.
     */
    public function isExpired()
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }

    /**
     * Increment the download count.
     */
    public function incrementDownloadCount()
    {
        $this->increment('download_count');
    }

    public function dataActivity()
    {
        return $this->belongsTo(DataActivity::class);
    }
}
