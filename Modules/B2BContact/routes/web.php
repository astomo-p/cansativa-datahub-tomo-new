<?php

use Illuminate\Support\Facades\Route;
use Modules\B2BContact\Http\Controllers\B2BContactController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('b2bcontacts', B2BContactController::class)->names('b2bcontact');
});
