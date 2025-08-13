<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToMerchant;

class UserCertificate extends Model
{
    use BelongsToMerchant;
    protected $fillable = [
        'user_id',
        'certificate_download_id',
        'status',
        'assigned_at'
    ];

    protected $casts = [
        'assigned_at' => 'datetime'
    ];

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
