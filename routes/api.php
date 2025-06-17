<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Datahub\B2C\ContactDataController;

Route::controller(ContactDataController::class)->prefix('v2')->group(function (){
    Route::get('/datahub/b2c/contact/top-five-area-pharmacies', 'topFiveAreaPharmacies');
    Route::get('/datahub/b2c/contact/top-five-purchase-pharmacies', 'topFivePurchasePharmacies');
    Route::get('/datahub/b2c/contact/contact-growth', 'contactGrowth');
    Route::get('/datahub/b2c/contact/top-contact-card', 'topContactCard');
    Route::get('/datahub/b2c/contact/community-data/all', 'allCommunityData');
    Route::get('/datahub/b2c/contact/community-data/id/{id}', 'communityDataById');
    Route::post('/datahub/b2c/contact/community-data/add', 'addCommunityData');
    Route::put('/datahub/b2c/contact/community-data/id/{id}', 'updateCommunityDataById');
    Route::delete('/datahub/b2c/contact/community-data/id/{id}', 'deleteCommunityDataById');
    });

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
