<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataActivityController;
use App\Http\Controllers\Instruktur\LoginInstrukturController;
use App\Http\Controllers\Instruktur\InstrukturManagementController;

Route::resource('data-activities', DataActivityController::class);

Route::get('/data-activities/{id}', [DataActivityController::class, 'show'])
    ->name('data-activities.show');

Route::post('instruktur/login', [LoginInstrukturController::class, 'logininstruktur']);
Route::apiResource('instruktur', InstrukturManagementController::class);



