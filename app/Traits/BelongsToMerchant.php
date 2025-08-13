<?php

namespace App\Traits;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToMerchant
{
    protected static function bootBelongsToMerchant()
    {
        static::addGlobalScope('merchant', function (Builder $builder) {
            if (Auth::check() && Auth::user() instanceof Admin) {
                $builder->where(self::getTable() . '.merchant_id', Auth::user()->merchant_id);
            }
        });
    }
}