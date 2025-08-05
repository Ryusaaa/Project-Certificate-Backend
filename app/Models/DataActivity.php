<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataActivity extends Model
{
    protected $table = 'data_activity';

    protected $fillable = [
        'activity_name',
        'date',
        'time', 
        'activity_type_id',
        'description',
        'instruktur_id'
    ];

    protected $casts = [
        'date' => 'datetime',
        'time' => 'string'
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
