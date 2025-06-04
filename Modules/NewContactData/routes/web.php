<?php

use Illuminate\Support\Facades\Route;
use Modules\NewContactData\Http\Controllers\NewContactDataController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('newcontactdatas', NewContactDataController::class)->names('newcontactdata');
});
