<?php

use Illuminate\Support\Facades\Route;
use Modules\NewContactData\Http\Controllers\NewContactDataController;

Route::prefix('v1')->name('api.')->group(function () {
    Route::get('top-five-area-pharmacies',[NewContactDataController::class,"topFiveAreaPharmacies"]);
    Route::get('top-five-purchase-pharmacies', [NewContactDataController::class, "topFivePurchasePharmacies"]);
    Route::get('contact-growth', [NewContactDataController::class, "contactGrowth"]);
    Route::get('top-contact-card', [NewContactDataController::class, "topContactCard"]);
    Route::get("contact/pharmacy-data/all", [NewContactDataController::class, "allPharmacyData"]);
    Route::get("contact/pharmacy-data/id/{id}", [NewContactDataController::class, "pharmacyDataById"]);
    Route::post("contact/pharmacy-data/add", [NewContactDataController::class, "addPharmacyData"]);
    Route::put("contact/pharmacy-data/id/{id}", [NewContactDataController::class, "updatePharmacyDataById"]);
    Route::delete("contact/pharmacy-data/id/{id}", [NewContactDataController::class, "deletePharmacyDataById"]);
    Route::get('contact/supplier-data/all', [NewContactDataController::class, "allSupplierData"]);
    Route::get('contact/supplier-data/id/{id}', [NewContactDataController::class, "supplierDataById"]);
    Route::post('contact/supplier-data/add', [NewContactDataController::class, "addSupplierData"]);
    Route::put('contact/supplier-data/id/{id}', [NewContactDataController::class, "updateSupplierDataById"]);
    Route::delete('contact/supplier-data/id/{id}', [NewContactDataController::class, "deleteSupplierDataById"]);
    Route::get('contact/community-data/all', [NewContactDataController::class, "allCommunityData"]);
    Route::get('contact/community-data/id/{id}', [NewContactDataController::class, "communityDataById"]);
    Route::post('contact/community-data/add', [NewContactDataController::class, "addCommunityData"]);
    Route::put('contact/community-data/id/{id}', [NewContactDataController::class, "updateCommunityDataById"]);
    Route::delete('contact/community-data/id/{id}', [NewContactDataController::class, "deleteCommunityDataById"]);
    Route::get('contact/general-newsletter-data/all', [NewContactDataController::class, "allGeneralNewsletterData"]);
    Route::get('contact/general-newsletter-data/id/{id}', [NewContactDataController::class, "generalNewsletterDataById"]);
    Route::post('contact/general-newsletter-data/add', [NewContactDataController::class, "addGeneralNewsletterData"]);
    Route::put('contact/general-newsletter-data/id/{id}', [NewContactDataController::class, "updateGeneralNewsletterDataById"]);
    Route::delete('contact/general-newsletter-data/id/{id}', [NewContactDataController::class, "deleteGeneralNewsletterDataById"]);
    Route::get('contact/pharmacy-database/parent/{parentId}', [NewContactDataController::class, "pharmacyDatabaseByParentId"]);
    Route::post('contact/pharmacy-database/add', [NewContactDataController::class, "addPharmacyDatabase"]);
    Route::post('contact/minio-upload', [NewContactDataController::class, "minioUpload"]);
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::get('/sanctum',function(Request $request){
        return response(["status"=>"success","message"=>"yes"],200);
    });
});
