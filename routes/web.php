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
use App\Http\Controllers\Sertifikat\SertifikatTemplateController;
use App\Http\Controllers\Sertifikat\SertifikatPesertaController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- DATA ACTIVITY ROUTES ---
// Route spesifik harus selalu berada di atas route resource atau route dinamis.
Route::put('data-activities/{id}/sertifikat-template', [DataActivityController::class, 'updateSertifikatTemplate']);
Route::get('data-activities/certificate-templates', [DataActivityController::class, 'getCertificateTemplates']);
Route::post('data-activities/{id}/import', [UserController::class, 'import']);
Route::post('data-activities/{id}/users', [UserController::class, 'inputUserDataActivity']);
Route::post('data-activities/{id}/set-template', [DataActivityController::class, 'setCertificateTemplate']);
// Route::resource menangani index, store, show, update, destroy secara otomatis.
Route::resource('data-activities', DataActivityController::class);
Route::resource('data-activity-types', DataActivityTypeController::class);


// --- USER & CERTIFICATE ROUTES ---
Route::get('users/download-template', [UserController::class, 'downloadTemplate'])->name('users.downloadTemplate');
Route::resource('users', UserController::class);

// --- ADMIN & INSTRUKTUR AUTH & MANAGEMENT ---
Route::post('roles', [RoleController::class, 'store']);

// Instruktur
Route::post('instruktur/login', [LoginInstrukturController::class, 'logininstruktur']);
Route::post('instruktur/logout', [LoginInstrukturController::class, 'logout'])->middleware('auth:sanctum');
Route::apiResource('instruktur', InstrukturManagementController::class);

// Admin
Route::post('admins/login', [LoginController::class, 'login']);
Route::post('admins/logout', [LoginController::class, 'logout'])->middleware('auth:sanctum');
Route::resource('admins', UserApiController::class);
Route::post('admins/create', [UserApiController::class, 'store']);
Route::put('admins/edit/{admin}', [UserApiController::class, 'update']);
Route::delete('admins/delete/{admin}', [UserApiController::class, 'destroy']);


// --- SERTIFIKAT TEMPLATE ROUTES ---
Route::prefix('sertifikat-templates')->group(function () {
    Route::get('/', [SertifikatTemplateController::class, 'index']);
    Route::post('/', [SertifikatTemplateController::class, 'store']);
    Route::get('/{id}', [SertifikatTemplateController::class, 'show']);
    Route::put('/{id}', [SertifikatTemplateController::class, 'update']);
    Route::delete('/{id}', [SertifikatTemplateController::class, 'destroy']);
    
    Route::post('/upload-image', [SertifikatTemplateController::class, 'uploadImage']);
    
    // PDF related routes
    Route::post('/{id}/preview-template', [SertifikatPesertaController::class, 'previewPDF']);
    Route::post('/{id}/generate-pdf', [SertifikatPesertaController::class, 'generatePDF']);
    Route::post('/{id}/generate-bulk-pdf', [SertifikatPesertaController::class, 'generateBulkPDF']);
    Route::get('/download/{token}', [SertifikatPesertaController::class, 'downloadPDF']);
    Route::get('/preview/{token}', [SertifikatPesertaController::class, 'previewPDFWithToken']);
    Route::get('/users/{userId}/certificates', [SertifikatPesertaController::class, 'getUserCertificates']);
});


// --- DEBUG & FALLBACK ROUTES ---
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

// Fallback route, harus selalu di paling bawah
Route::get('{any}', function () {
    return response()->json(['message' => 'Not Found'], 404);
})->where('any', '.*');
