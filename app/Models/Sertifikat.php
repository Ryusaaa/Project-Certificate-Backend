<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sertifikat extends Model
{
    protected $fillable = [
        'name',
        'background_image',
        'layout',
        'elements',
        'is_active'
    ];

    protected $casts = [
        'layout' => 'array',
        'elements' => 'array',
        'is_active' => 'boolean'
    ];

    public $timestamps = true;

    /**
     * Get all downloads for this certificate template.
     */
    public function downloads()
    {
        return $this->hasMany(CertificateDownload::class);
    }

    /**
     * Create a new download record for this certificate.
     */
    public function createDownload($data)
    {
        return $this->downloads()->create([
            'token' => $data['token'],
            'filename' => $data['filename'],
            'recipient_name' => $data['recipient_name'],
            'certificate_number' => $data['certificate_number'],
            'user_id' => $data['user_id'] ?? null,
            'expires_at' => $data['expires_at'] ?? null
        ]);
    }
}
