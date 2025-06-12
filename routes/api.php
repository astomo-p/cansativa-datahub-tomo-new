<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Datahub\B2C\ContactDataController;

Route::controller(ContactDataController::class)->prefix('v1')->group(function (){
    Route::get('/datahub/b2c/contact/top-five-area-pharmacies', 'topFiveAreaPharmacies');
    Route::get('/datahub/b2c/contact/top-five-purchase-pharmacies', 'topFivePurchasePharmacies');
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
