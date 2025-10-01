<?php

use Illuminate\Support\Facades\Route;
use Modules\B2BContact\Http\Controllers\B2BContactController;
use Modules\B2BContact\Http\Controllers\ExternalApiController;
use Modules\B2BContact\Http\Controllers\GeneralNewsletterController;
use Modules\B2BContact\Http\Controllers\HistoryExportController;
use Modules\B2BContact\Http\Controllers\PharmacyController;
use Modules\B2BContact\Http\Controllers\SavedFilterController;
use Modules\B2BContact\Http\Controllers\SupplierController;

Route::prefix('v1')->group(function () {
    Route::post('datahub/b2b/export', [B2BContactController::class, "exportData"]);
    Route::post('datahub/import/preview', [B2BContactController::class, "previewImportContact"]);
    Route::post('datahub/import/save', [B2BContactController::class, "saveImportContact"]);
    Route::get('datahub/import/column-mapping', [B2BContactController::class, "getImportColumnMapping"]);
    Route::get('datahub/import/download-sample', [B2BContactController::class, "downloadSampleImport"]);
    Route::get('datahub/import/download/{path}', [B2BContactController::class, 'downloadSample'])->where('path', '.*')->name('download.sample')->middleware('signed');
    
    Route::get("datahub/b2b/contact-types", [B2BContactController::class, "getB2BContactTypes"]);
    Route::get("datahub/b2b/contact/metrics", [B2BContactController::class, "getMetricsData"]);
    Route::get("datahub/b2c/contact/metrics", [B2BContactController::class, "getB2CMetricsData"]);
    Route::get("datahub/view-contact-report", [B2BContactController::class, "getContactReport"]);
    Route::get("datahub/review-campaign-contact", [B2BContactController::class, "reviewExportCampaignContact"]);

    Route::get("datahub/default-fields/{contact_type_id}", [B2BContactController::class, "getDefaultFields"]);
    Route::get("datahub/custom-fields/{contact_type_id}", [B2BContactController::class, "getCustomFields"]);
    Route::post("datahub/custom-fields/{contact_type_id}", [B2BContactController::class, "addCustomField"]);
    Route::get("datahub/check-custom-fields/{contact_type_id}/{field_name}", [B2BContactController::class, "checkCustomField"]);
    
    Route::get("datahub/b2b/contact/contact-filters", [B2BContactController::class, "contactFilters"]);
    Route::get("datahub/contact/data-filter/{contact_type_id}/{column}", [B2BContactController::class, "contactDropdownFilter"]);
    Route::get("datahub/contact/check-by-phone", [B2BContactController::class, "checkContactByPhone"]);
    Route::get("datahub/contact/detail/{id}/{type}", [B2BContactController::class, "getContactDetailById"]);

    Route::get("datahub/b2b/contact/pharmacy-data/all", [PharmacyController::class, "allPharmacyData"]);
    Route::get("datahub/b2b/contact/pharmacy-data/id/{id}", [PharmacyController::class, "pharmacyDataById"]);
    Route::post("datahub/b2b/contact/pharmacy-data/add", [PharmacyController::class, "addPharmacyData"]);
    Route::put("datahub/b2b/contact/pharmacy-data/id/{id}", [PharmacyController::class, "updatePharmacyDataById"]);
    Route::delete("datahub/b2b/contact/pharmacy-data/id/{id}", [PharmacyController::class, "deletePharmacyDataById"]);
    Route::put("datahub/b2b/contact/pharmacy-sponsor/{id}", [PharmacyController::class, "updateSponsorStatus"]);

    Route::get('datahub/b2b/contact/supplier-data/all', [SupplierController::class, "allSupplierData"]);
    Route::get('datahub/b2b/contact/supplier-data/id/{id}', [SupplierController::class, "supplierDataById"]);
    Route::post('datahub/b2b/contact/supplier-data/add', [SupplierController::class, "addSupplierData"]);
    Route::put('datahub/b2b/contact/supplier-data/id/{id}', [SupplierController::class, "updateSupplierDataById"]);
    Route::delete('datahub/b2b/contact/supplier-data/id/{id}', [SupplierController::class, "deleteSupplierDataById"]);

    Route::get('datahub/b2b/contact/top-five-area-pharmacies',[B2BContactController::class,"topFiveAreaPharmacies"]);
    Route::get('datahub/b2b/contact/top-five-purchase-pharmacies', [B2BContactController::class, "topFivePurchasePharmacies"]);
    Route::get('datahub/b2b/contact/contact-growth', [B2BContactController::class, "contactGrowthB2B"]);
    Route::get('datahub/b2b/contact/top-contact-card', [B2BContactController::class, "topContactCardB2B"]);
    Route::get('datahub/b2b/contact/top-five-pharmacies-by-db', [B2BContactController::class, "topFivePharmaciesByDatabase"]);
    Route::get('datahub/b2b/contact/community-user-by-age', [B2BContactController::class, "getCommunityUserByAge"]);


    Route::get('datahub/b2b/contact/general-newsletter-data/all', [GeneralNewsletterController::class, "allGeneralNewsletterData"]);
    Route::get('datahub/b2b/contact/general-newsletter-data/id/{id}', [GeneralNewsletterController::class, "generalNewsletterDataById"]);
    Route::post('datahub/b2b/contact/general-newsletter-data/add', [GeneralNewsletterController::class, "addGeneralNewsletterData"]);
    Route::put('datahub/b2b/contact/general-newsletter-data/id/{id}', [GeneralNewsletterController::class, "updateGeneralNewsletterDataById"]);
    Route::delete('datahub/b2b/contact/general-newsletter-data/id/{id}', [GeneralNewsletterController::class, "deleteGeneralNewsletterDataById"]);

    Route::post('datahub/b2b/contact/history-exports/add', [HistoryExportController::class, "addHistoryExport"]);
    Route::put('datahub/b2b/contact/history-exports/id/{id}', [HistoryExportController::class, "updateHistoryExport"]);
    Route::get('datahub/b2b/contact/history-exports/all', [HistoryExportController::class, "getAllHistoryExports"]);
    Route::get('datahub/b2b/contact/history-exports/id/{id}', [HistoryExportController::class, "historyExportDataById"]);

    Route::get('datahub/b2b/contact/saved-filter-dropdown', [SavedFilterController::class, "getAllFilter"]);
    Route::get('datahub/b2b/contact/filter/all', [SavedFilterController::class, "getFilterTableList"]);
    Route::get('datahub/b2b/contact/filter/id/{id}', [SavedFilterController::class, "getFilterDetail"]);
    Route::post('datahub/b2b/contact/filter/add', [SavedFilterController::class, "saveNewFilter"]);
    Route::put('datahub/b2b/contact/filter/id/{id}', [SavedFilterController::class, "updateFilter"]);
    Route::delete('datahub/b2b/contact/filter/id/{id}', [SavedFilterController::class, "deleteFilter"]);

    Route::get('datahub/file/{id}', [B2BContactController::class, "readFile"]);
    Route::post('datahub/file', [B2BContactController::class, "addFile"]);
    Route::delete('datahub/file/{id}', [B2BContactController::class, "deleteFile"]);

    Route::post('datahub/b2b/upload-file', [B2BContactController::class, "uploadFile"]);
    Route::get('datahub/b2b/file-content/{id}', [B2BContactController::class, "getFileContent"]);

    Route::post('datahub/b2b/contact/preview-contacts', [B2BContactController::class, "previewExportContacts"]);
    Route::get('datahub/b2b/contact/template-message/all', [B2BContactController::class, "getKeyAccountManageTemplateMessage"]);
    Route::get('datahub/b2b/contact/template-message/id/{id}', [B2BContactController::class, "getKeyAccountManageTemplateMessagebyId"]);
    Route::post('datahub/b2b/contact/key-account-manager/add', [B2BContactController::class, "addKeyAccountManager"]);
    Route::put('datahub/b2b/contact/key-account-manager/id/{id}', [B2BContactController::class, "editKeyAccountManager"]);
    Route::delete('datahub/b2b/contact/key-account-manager/id/{id}', [B2BContactController::class, "deleteKeyAccountManager"]);

    Route::get('datahub/country-codes', [B2BContactController::class, "getCountryCode"]);
    Route::post('external/shop-upload-file', [ExternalApiController::class, "uploadInfoShop"]);
});

Route::prefix('v1')->name('api.')->group(function () {
    // Route::get('datahub/b2c/contact/subscriber-data/all', [B2BContactController::class, "allSubscriberData"]);
    // Route::get('datahub/b2c/contact/subscriber-data/id/{id}', [B2BContactController::class, "subscriberDataById"]);
    // Route::post('datahub/b2c/contact/subscriber-data/add', [B2BContactController::class, "addSubscriberData"]);
    // Route::put('datahub/b2c/contact/subscriber-data/id/{id}', [B2BContactController::class, "updateSubscriberDataById"]);
    // Route::delete('datahub/b2c/contact/subscriber-data/id/{id}', [B2BContactController::class, "deleteSubscriberDataById"]);
    /* Route::post('datahub/b2c/contact/minio-upload', [B2BContactController::class, "minioUpload"]);
     */
    // Route::get('woocommerce/customers',[B2BContactController::class,'woocommerceCustomers']);  

});
Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
        Route::post('datahub/b2b/contact/export-newsletter', [B2BContactController::class, "exportToNewsletter"]);
        // Route::get("datahub/b2b/contact/pharmacy-data/all", [PharmacyController::class, "allPharmacyData"]);

    // Route::get('datahub/b2b/contact/pharmacy-database/parent/{parentId}', [B2BContactController::class, "pharmacyDatabaseByParentId"]);
    // Route::post('datahub/b2b/contact/pharmacy-database/add', [B2BContactController::class, "addPharmacyDatabase"]);
    // Route::get('datahub/b2b/contact/pharmacy-database/parent/{parentId}/id/{id}', [B2BContactController::class, "pharmacyDatabaseByParentIdAndId"]);
    // Route::put('datahub/b2b/contact/pharmacy-database/parent/{parentId}/id/{id}', [B2BContactController::class, "updatePharmacyDatabaseByParentIdAndId"]);
    // Route::delete('datahub/b2b/contact/pharmacy-database/parent/{parentId}/id/{id}', [B2BContactController::class, "deletePharmacyDatabaseByParentIdAndId"]);
    // Route::get("datahub/b2b/contact/pharmacy-data/all", [B2BContactController::class, "allPharmacyData"]);
    // Route::get("datahub/b2b/contact/pharmacy-data/id/{id}", [B2BContactController::class, "pharmacyDataById"]);
    // Route::post("datahub/b2b/contact/pharmacy-data/add", [B2BContactController::class, "addPharmacyData"]);
    // Route::put("datahub/b2b/contact/pharmacy-data/id/{id}", [B2BContactController::class, "updatePharmacyDataById"]);
    // Route::delete("datahub/b2b/contact/pharmacy-data/id/{id}", [B2BContactController::class, "deletePharmacyDataById"]);
    // Route::get('datahub/b2b/contact/supplier-data/all', [B2BContactController::class, "allSupplierData"]);
    // Route::get('datahub/b2b/contact/supplier-data/id/{id}', [B2BContactController::class, "supplierDataById"]);
    // Route::post('datahub/b2b/contact/supplier-data/add', [B2BContactController::class, "addSupplierData"]);
    // Route::put('datahub/b2b/contact/supplier-data/id/{id}', [B2BContactController::class, "updateSupplierDataById"]);
    // Route::delete('datahub/b2b/contact/supplier-data/id/{id}', [B2BContactController::class, "deleteSupplierDataById"]);
    // Route::get('datahub/b2b/contact/top-five-area-pharmacies',[B2BContactController::class,"topFiveAreaPharmacies"]);
    // Route::get('datahub/b2b/contact/top-five-purchase-pharmacies', [B2BContactController::class, "topFivePurchasePharmacies"]);
    // Route::get('datahub/b2b/contact/contact-growth', [B2BContactController::class, "contactGrowthB2B"]);
    // Route::get('datahub/b2b/contact/top-contact-card', [B2BContactController::class, "topContactCardB2B"]);
    // Route::get('datahub/b2b/contact/top-five-pharmacies-by-db', [B2BContactController::class, "topFivePharmaciesByDatabase"]);
});
