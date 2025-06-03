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
    Route::get('top-five-area-pharmacies',[ContactDataController::class,"topFiveAreaPharmacies"]);
    Route::get('top-five-purchase-pharmacies', [ContactDataController::class, "topFivePurchasePharmacies"]);
    Route::get('contact-growth', [ContactDataController::class, "contactGrowth"]);
    Route::get('top-contact-card', [ContactDataController::class, "topContactCard"]);
    Route::get("contact/pharmacy-data/all", [ContactDataController::class, "allPharmacyData"]);
    Route::get("contact/pharmacy-data/id/{id}", [ContactDataController::class, "pharmacyDataById"]);
    Route::post("contact/pharmacy-data/add", [ContactDataController::class, "addPharmacyData"]);
    Route::put("contact/pharmacy-data/id/{id}", [ContactDataController::class, "updatePharmacyDataById"]);
    Route::delete("contact/pharmacy-data/id/{id}", [ContactDataController::class, "deletePharmacyDataById"]);
    Route::get('contact/supplier-data/all', [ContactDataController::class, "allSupplierData"]);
    Route::get('contact/supplier-data/id/{id}', [ContactDataController::class, "supplierDataById"]);
    Route::post('contact/supplier-data/add', [ContactDataController::class, "addSupplierData"]);
    Route::put('contact/supplier-data/id/{id}', [ContactDataController::class, "updateSupplierDataById"]);
    Route::delete('contact/supplier-data/id/{id}', [ContactDataController::class, "deleteSupplierDataById"]);
    Route::get('contact/community-data/all', [ContactDataController::class, "allCommunityData"]);
    Route::get('contact/community-data/id/{id}', [ContactDataController::class, "communityDataById"]);
    Route::post('contact/community-data/add', [ContactDataController::class, "addCommunityData"]);
    Route::put('contact/community-data/id/{id}', [ContactDataController::class, "updateCommunityDataById"]);
    Route::delete('contact/community-data/id/{id}', [ContactDataController::class, "deleteCommunityDataById"]);
    Route::get('contact/general-newsletter-data/all', [ContactDataController::class, "allGeneralNewsletterData"]);
    Route::get('contact/general-newsletter-data/id/{id}', [ContactDataController::class, "generalNewsletterDataById"]);
    Route::post('contact/general-newsletter-data/add', [ContactDataController::class, "addGeneralNewsletterData"]);
    Route::put('contact/general-newsletter-data/id/{id}', [ContactDataController::class, "updateGeneralNewsletterDataById"]);
    Route::delete('contact/general-newsletter-data/id/{id}', [ContactDataController::class, "deleteGeneralNewsletterDataById"]);
    Route::get('contact/pharmacy-database/parent/{parentId}', [ContactDataController::class, "pharmacyDatabaseByParentId"]);
    Route::post('contact/pharmacy-database/add', [ContactDataController::class, "addPharmacyDatabase"]);
    Route::post('contact/minio-upload', [ContactDataController::class, "minioUpload"]);
});

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('contactdata', fn (Request $request) => $request->user())->name('contactdata');
});
