<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataActivityController;



Route::middleware('api')->group(function () {
    Route::apiResource('data-activities', DataActivityController::class);
});
