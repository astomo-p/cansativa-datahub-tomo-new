<?php

use Illuminate\Support\Facades\Route;
use Modules\CustomAnalytics\Http\Controllers\CustomAnalyticsController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('customanalytics', CustomAnalyticsController::class)->names('customanalytics');
});
