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
}
