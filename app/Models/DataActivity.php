<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataActivity extends Model
{
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

    protected $casts = [
        'date' => 'datetime',
        'time_start' => 'string',
        'time_end' => 'string'
    ];

    public function activityType()
    {
        return $this->belongsTo(DataActivityType::class, 'activity_type_id');
    }

    public function instruktur()
    {
        return $this->belongsTo(Instruktur::class, 'instruktur_id');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'data_activity_user');
    }
}
