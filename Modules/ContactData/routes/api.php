<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\ContactData\App\Http\Controllers\ContactDataController;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::prefix('v1')->name('api.')->group(function () {
    Route::get('old-top-five-area-pharmacies',[ContactDataController::class,"topFiveAreaPharmacies"]);
    Route::get('old-top-five-purchase-pharmacies', [ContactDataController::class, "topFivePurchasePharmacies"]);
    Route::get('old-contact-growth', [ContactDataController::class, "contactGrowth"]);
    Route::get('old-top-contact-card', [ContactDataController::class, "topContactCard"]);
    Route::get("old-contact/pharmacy-data/all", [ContactDataController::class, "allPharmacyData"]);
    Route::get("old-contact/pharmacy-data/id/{id}", [ContactDataController::class, "pharmacyDataById"]);
    Route::post("old-contact/pharmacy-data/add", [ContactDataController::class, "addPharmacyData"]);
    Route::put("old-contact/pharmacy-data/id/{id}", [ContactDataController::class, "updatePharmacyDataById"]);
    Route::delete("old-contact/pharmacy-data/id/{id}", [ContactDataController::class, "deletePharmacyDataById"]);
    Route::get('old-contact/supplier-data/all', [ContactDataController::class, "allSupplierData"]);
    Route::get('old-contact/supplier-data/id/{id}', [ContactDataController::class, "supplierDataById"]);
    Route::post('old-contact/supplier-data/add', [ContactDataController::class, "addSupplierData"]);
    Route::put('old-contact/supplier-data/id/{id}', [ContactDataController::class, "updateSupplierDataById"]);
    Route::delete('old-contact/supplier-data/id/{id}', [ContactDataController::class, "deleteSupplierDataById"]);
    Route::get('old-contact/community-data/all', [ContactDataController::class, "allCommunityData"]);
    Route::get('old-contact/community-data/id/{id}', [ContactDataController::class, "communityDataById"]);
    Route::post('old-contact/community-data/add', [ContactDataController::class, "addCommunityData"]);
    Route::put('old-contact/community-data/id/{id}', [ContactDataController::class, "updateCommunityDataById"]);
    Route::delete('old-contact/community-data/id/{id}', [ContactDataController::class, "deleteCommunityDataById"]);
    Route::get('old-contact/general-newsletter-data/all', [ContactDataController::class, "allGeneralNewsletterData"]);
    Route::get('old-contact/general-newsletter-data/id/{id}', [ContactDataController::class, "generalNewsletterDataById"]);
    Route::post('old-contact/general-newsletter-data/add', [ContactDataController::class, "addGeneralNewsletterData"]);
    Route::put('old-contact/general-newsletter-data/id/{id}', [ContactDataController::class, "updateGeneralNewsletterDataById"]);
    Route::delete('old-contact/general-newsletter-data/id/{id}', [ContactDataController::class, "deleteGeneralNewsletterDataById"]);
    Route::get('old-contact/pharmacy-database/parent/{parentId}', [ContactDataController::class, "pharmacyDatabaseByParentId"]);
    Route::post('old-contact/pharmacy-database/add', [ContactDataController::class, "addPharmacyDatabase"]);
    Route::post('old-contact/minio-upload', [ContactDataController::class, "minioUpload"]);
});

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('contactdata', fn (Request $request) => $request->user())->name('contactdata');
});
