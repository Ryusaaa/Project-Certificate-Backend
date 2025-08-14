<?php

namespace App\Models;

use App\Traits\BelongsToMerchant;
use Illuminate\Database\Eloquent\Model;

class DataActivityType extends Model
{
    use BelongsToMerchant;
    protected $fillable = [
        'merchant_id',
        'type_name',
    ];

    public function activities()
    {
        return $this->hasMany(DataActivity::class, 'activity_type_id');
    }
}
