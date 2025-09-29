<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomAnalytics\Http\Controllers\CustomAnalyticsController;

Route::prefix('v1')->group(function () {
    Route::get('custom/analytics/all', [CustomAnalyticsController::class,'allData']);
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('customanalytics', CustomAnalyticsController::class)->names('customanalytics');
});
