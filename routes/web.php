<?php


use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\UserApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataActivityController;
use App\Http\Controllers\Instruktur\LoginInstrukturController;
use App\Http\Controllers\Instruktur\InstrukturManagementController;
use App\Http\Controllers\DataActivityTypeController;



Route::resource('data-activity-types', DataActivityTypeController::class);
Route::resource('data-activities', DataActivityController::class);
Route::resource('users', UserController::class);

Route::get('/data-activities/{id}', [DataActivityController::class, 'show'])
    ->name('data-activities.show');

Route::post('instruktur/login', [LoginInstrukturController::class, 'logininstruktur']);
Route::apiResource('instruktur', InstrukturManagementController::class);

Route::post('admins/login', [LoginController::class, 'login']);
Route::resource('admins', UserApiController::class);
Route::post('admins/create', [UserApiController::class, 'store']);
Route::put('admins/edit/{admin}', [UserApiController::class, 'update']);
Route::delete('admins/delete/{admin}', [UserApiController::class, 'destroy']);
