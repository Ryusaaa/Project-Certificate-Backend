<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataActivity extends Model
{
    protected $table = 'data_activity';

    protected $fillable = [
        'activity_name',
        'activity_type_id',
        'description',
    ];

    public function activityType()
    {
        return $this->belongsTo(DataActivityType::class, 'activity_type_id');
    }
}
