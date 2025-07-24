<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataActivityController;

Route::resource('data-activities', DataActivityController::class);

Route::get('/data-activities/{id}', [DataActivityController::class, 'show'])
    ->name('data-activities.show');

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


