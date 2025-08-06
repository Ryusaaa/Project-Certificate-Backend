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
use Dflydev\DotAccessData\Data;
use App\Http\Controllers\Sertifikat\SertifikatTemplateController;

Route::get('/debug-cors', function (\Illuminate\Http\Request $request) {
    \Illuminate\Support\Facades\Log::debug('DEBUG GET CORS HIT', [
        'Origin' => $request->headers->get('Origin'),
        'Headers' => $request->headers->all(),
    ]);

    return response()->json(['ok' => true]);
});


Route::get('/phpinfo', function() {
    phpinfo();
});
Route::get('/data-activities/{id}', [DataActivityController::class, 'show'])
    ->name('data-activities.show');
Route::get('users/download-template', [UserController::class, 'downloadTemplate'])
    ->name('users.downloadTemplate');    
Route::post('data-activities/{id}/import', [UserController::class, 'import']);

Route::resource('data-activity-types', DataActivityTypeController::class);

Route::resource('data-activities', DataActivityController::class);
Route::resource('users', UserController::class);    

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

// Sertifikat Template Routes
Route::prefix('sertifikat-templates')->group(function () {
    Route::get('/', [SertifikatTemplateController::class, 'index']);
    Route::get('/editor', function() {
        return view('sertifikat.editor');
    });
    Route::post('/upload-image', [SertifikatTemplateController::class, 'uploadImage']);
    Route::post('/', [SertifikatTemplateController::class, 'store']);
    Route::get('/{id}', [SertifikatTemplateController::class, 'show']);
    Route::put('/{id}', [SertifikatTemplateController::class, 'update']);
    Route::delete('/{id}', [SertifikatTemplateController::class, 'destroy']);
    Route::post('/{id}/toggle-active', [SertifikatTemplateController::class, 'toggleActive']);
    Route::post('/{id}/generate-pdf', [SertifikatTemplateController::class, 'generatePDF']);
});

Route::get('{any}', function () {
    return response()->json([], 204);
})->where('any', '.*');
