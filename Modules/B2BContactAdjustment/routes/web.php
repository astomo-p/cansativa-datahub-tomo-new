<?php

use Illuminate\Support\Facades\Route;
use Modules\B2BContactAdjustment\Http\Controllers\B2BContactAdjustmentController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('b2bcontactadjustments', B2BContactAdjustmentController::class)->names('b2bcontactadjustment');
});
