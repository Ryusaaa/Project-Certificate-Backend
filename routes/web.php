<?php


use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataActivityController;
use App\Http\Controllers\Instruktur\LoginInstrukturController;
use App\Http\Controllers\Instruktur\InstrukturManagementController;



Route::resource('data-activity-types', DataActivityTypeController::class);
Route::resource('data-activities', DataActivityController::class);
Route::resource('users', UserController::class);

Route::get('/data-activities/{id}', [DataActivityController::class, 'show'])
    ->name('data-activities.show');

Route::post('instruktur/login', [LoginInstrukturController::class, 'logininstruktur']);
Route::apiResource('instruktur', InstrukturManagementController::class);



