<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataActivityType extends Model
{
    protected $fillable = [
        'type_name',
    ];

    public function activities()
    {
        return $this->hasMany(DataActivity::class, 'activity_type_id');
    }
}
