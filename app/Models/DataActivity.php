<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataActivity extends Model
{
    protected $table = 'data_activity';

    protected $fillable = [
        'activity_name',
        'date',
        'activity_type_id',
        'description',
        'instruktur_id'
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function activityType()
    {
        return $this->belongsTo(DataActivityType::class, 'activity_type_id');
    }

    public function instruktur()
    {
        return $this->belongsTo(Instruktur::class, 'instruktur_id');
    }
}
