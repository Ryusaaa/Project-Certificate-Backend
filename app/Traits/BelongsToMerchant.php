<?php

namespace App\Traits;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToMerchant
{
    protected static function bootBelongsToMerchant()
    {
        // Scope untuk memfilter data saat dibaca (SELECT)
        static::addGlobalScope('merchant', function (Builder $builder) {
            if (Auth::check() && Auth::user() instanceof Admin) {
                $builder->where(self::getTable() . '.merchant_id', Auth::user()->merchant_id);
            }
        });

        // Event untuk mengisi merchant_id secara otomatis saat data dibuat (INSERT)
        static::creating(function ($model) {
            if (Auth::check() && Auth::user() instanceof Admin) {
                // Hanya isi jika merchant_id belum diatur sebelumnya
                if (!$model->merchant_id) {
                    $model->merchant_id = Auth::user()->merchant_id;
                }
            }
        });
    }
}