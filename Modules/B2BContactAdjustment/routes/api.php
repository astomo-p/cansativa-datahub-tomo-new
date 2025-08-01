<?php

use Illuminate\Support\Facades\Route;
use Modules\B2BContactAdjustment\Http\Controllers\B2BContactAdjustmentController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get("datahub/b2b/adj-contact/{contact_type_id}/{id}", [B2BContactAdjustmentController::class, "getContactDataById"]);
    Route::put('datahub/b2b/adj-contacts/{type}/{id}', [B2BContactAdjustmentController::class, 'updateContact'])
      ->where('type', '[1-4]'); 
});

Route::post('v1/datahub/minio-upload', [B2BContactAdjustmentController::class, 'handleFileUpload']);

