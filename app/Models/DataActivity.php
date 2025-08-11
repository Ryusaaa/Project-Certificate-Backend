<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DataActivity extends Model
{
    use HasFactory;
    
    // Sebaiknya gunakan nama tabel plural sesuai konvensi Laravel: 'data_activities'
    // Jika Anda tetap ingin menggunakan 'data_activity', pastikan tidak ada masalah di tempat lain.
    protected $table = 'data_activity';

    protected $fillable = [
        'activity_name',
        'date',
        'time_start', 
        'time_end', 
        'activity_type_id',
        'description',
        'instruktur_id'
    ];

    // Casting ini sudah baik untuk memastikan tipe data yang konsisten.
    protected $casts = [
        'date' => 'datetime',
        'time_start' => 'string',
        'time_end' => 'string'
    ];

    /**
     * Mendefinisikan relasi ke tipe kegiatan.
     */
    public function activityType()
    {
        return $this->belongsTo(DataActivityType::class, 'activity_type_id');
    }

    /**
     * Mendefinisikan relasi ke instruktur.
     */
    public function instruktur()
    {
        return $this->belongsTo(Instruktur::class, 'instruktur_id');
    }

    public function peserta()
    {
        return $this->belongsToMany(User::class, 'data_activity_user', 'data_activity_id', 'user_id');
    }

    public function sertifikat()
    {
        return $this->belongsTo(Sertifikat::class, 'sertifikat_id');
    }
}
