<?php


use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Admin\LoginController;
use App\Http\Controllers\Admin\UserApiController;
use App\Http\Controllers\RoleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DataActivity\DataActivityController;
use App\Http\Controllers\DataActivity\DataActivityTypeController;
use App\Http\Controllers\Instruktur\LoginInstrukturController;
use App\Http\Controllers\Instruktur\InstrukturManagementController;



Route::resource('data-activity-types', DataActivityTypeController::class);
Route::resource('data-activities', DataActivityController::class);
Route::resource('users', UserController::class);

Route::get('/data-activities/{id}', [DataActivityController::class, 'show'])
    ->name('data-activities.show');


Route::post('roles', [RoleController::class, 'store']);

Route::post('instruktur/login', [LoginInstrukturController::class, 'logininstruktur']);
Route::post('instruktur/logout', [LoginInstrukturController::class, 'logout'])->middleware('auth:sanctum');
Route::apiResource('instruktur', InstrukturManagementController::class);

Route::post('admins/login', [LoginController::class, 'login']);
Route::post('admins/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
Route::resource('admins', UserApiController::class);
Route::post('admins/create', [UserApiController::class, 'store']);
Route::put('admins/edit/{admin}', [UserApiController::class, 'update']);
Route::delete('admins/delete/{admin}', [UserApiController::class, 'destroy']);
