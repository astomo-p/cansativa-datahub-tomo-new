<?php

use Illuminate\Support\Facades\Route;
use Modules\NewAnalytics\Http\Controllers\NewAnalyticsController;

Route::prefix('v1')->name('api.')->group(function () {
    Route::get('analytics-monthly-visitor', [NewAnalyticsController::class,"analyticsMonthlyVisitor"])->name('analytics.monthly.visitor');
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('newanalytics', NewAnalyticsController::class)->names('newanalytics');
});
