<?php

use Illuminate\Support\Facades\Route;
use Modules\NewContactData\Http\Controllers\NewContactDataController;
use Modules\NewContactData\Http\Controllers\FileProcessorController;
use Modules\NewContactData\Http\Controllers\WoocommerceDataController;
use Modules\NewContactData\Http\Controllers\ContactGeoController;
use Modules\NewContactData\Http\Controllers\SavedFilterController;
use Modules\NewContactData\Http\Controllers\HistoryExportController;
use Modules\NewContactData\Http\Controllers\WaTemplateAttributesController;

Route::prefix('v1')->name('api.')->group(function () {
    Route::post('datahub/b2c/export', [NewContactDataController::class, "exportData"]);
    Route::post('datahub/b2c/import', [NewContactDataController::class, "importData"]);
    Route::post('datahub/b2c/import/save', [NewContactDataController::class, "importSave"]);
    Route::get('datahub/b2c/contact/contact-types-data/all', [NewContactDataController::class, "contactTypesData"]);
    //Route::get('datahub/b2c/contact/history-exports', [NewContactDataController::class, "historyExports"]);
    //Route::get('datahub/b2c/contact/history-exports/add', [NewContactDataController::class, "historyExportsAdd"]);
    //Route::get('datahub/b2c/contact/history-exports/id/{id}', [NewContactDataController::class, "historyExportsById"]);
    Route::post('datahub/b2c/contact/history-exports/add', [HistoryExportController::class, "addHistoryExport"]);
    Route::put('datahub/b2c/contact/history-exports/id/{id}', [HistoryExportController::class, "updateHistoryExport"]);
    Route::get('datahub/b2c/contact/history-exports/all', [HistoryExportController::class, "getAllHistoryExports"]);
    Route::get('datahub/b2c/contact/history-exports/id/{id}', [HistoryExportController::class, "historyExportDataById"]);
//Route::get('datahub/b2c/contact/saved-filters', [NewContactDataController::class, "savedFilters"]);
    //Route::post('datahub/b2c/contact/saved-filters/add', [NewContactDataController::class, "savedFiltersAdd"]);
    Route::get('datahub/b2c/contact/saved-filter-dropdown', [SavedFilterController::class, "getAllFilter"]);
    Route::get('datahub/b2c/contact/filter/all', [SavedFilterController::class, "getFilterTableList"]);
    Route::get('datahub/b2c/contact/filter/id/{id}', [SavedFilterController::class, "getFilterDetail"]);
    Route::post('datahub/b2c/contact/filter/add', [SavedFilterController::class, "saveNewFilter"]);
    Route::put('datahub/b2c/contact/filter/id/{id}', [SavedFilterController::class, "updateFilter"]);
    Route::delete('datahub/b2c/contact/filter/id/{id}', [SavedFilterController::class, "deleteFilter"]);
    Route::get("datahub/b2c/contact/metrics", [NewContactDataController::class, "getMetricsData"]);
    
    
    
    Route::put('datahub/b2c/contact/temporary-subscription', [NewContactDataController::class, "updateContactSubscriptionFromLogin"]);
    Route::get('datahub/b2c/contact/community-data/scroll', [NewContactDataController::class, "communityDataScroll"]);
    Route::get('datahub/b2c/contact/pharmacy-database/scroll', [NewContactDataController::class, "pharmacyDatabaseScroll"]);
    Route::get('datahub/b2c/contact/community-data/user-stats', [NewContactDataController::class, "communityDataUserStats"]);
    Route::get('datahub/b2c/contact/pharmacy-database/user-stats', [NewContactDataController::class, "pharmacyDatabaseUserStats"]);
    Route::get('datahub/b2c/export/contact-logs', [NewContactDataController::class, "exportContactLogs"]);
    Route::get('woocommerce/export', [WoocommerceDataController::class, "exportWoocommerceData"]);
    Route::get('datahub/b2c/contact/sample-data', [NewContactDataController::class, "sampleData"]);
    Route::delete('datahub/b2c/contact/saved-filters/id/{id}', [NewContactDataController::class, "deleteSavedFilterById"]);
    Route::put('datahub/b2c/contact/saved-filters/id/{id}', [NewContactDataController::class, "updateSavedFilterById"]);
    Route::get('datahub/b2c/contact/countries',[ContactGeoController::class,'getCountries']);
    Route::get('datahub/b2c/contact/country/{country}/cities',[ContactGeoController::class,'getCitiesByCountry']);
    Route::get('datahub/b2c/contact/country/{country}/city/{city}/postcodes',[ContactGeoController::class,'getPostcodesByCityAndCountry']);
    Route::post('datahub/contact/wa-template-attributes/add',[WaTemplateAttributesController::class,'addWaTemplateAttributes']);
    Route::get('datahub/contact/wa-template-attributes/all',[WaTemplateAttributesController::class,'allWaTemplateAttributes']);
    Route::delete('datahub/contact/wa-template-attributes/{id}',[WaTemplateAttributesController::class,'deleteWaTemplateAttributes']);
    
});

Route::prefix('v1')->name('api.')->group(function () {
   // Route::get("datahub/b2b/contact/pharmacy-data/all", [NewContactDataController::class, "allPharmacyData"]);
    // Route::get("datahub/b2b/contact/pharmacy-data/id/{id}", [NewContactDataController::class, "pharmacyDataById"]);
    // Route::post("datahub/b2b/contact/pharmacy-data/add", [NewContactDataController::class, "addPharmacyData"]);
    // Route::put("datahub/b2b/contact/pharmacy-data/id/{id}", [NewContactDataController::class, "updatePharmacyDataById"]);
    // Route::delete("datahub/b2b/contact/pharmacy-data/id/{id}", [NewContactDataController::class, "deletePharmacyDataById"]);
    // Route::get('datahub/b2b/contact/supplier-data/all', [NewContactDataController::class, "allSupplierData"]);
    // Route::get('datahub/b2b/contact/supplier-data/id/{id}', [NewContactDataController::class, "supplierDataById"]);
    // Route::post('datahub/b2b/contact/supplier-data/add', [NewContactDataController::class, "addSupplierData"]);
    // Route::put('datahub/b2b/contact/supplier-data/id/{id}', [NewContactDataController::class, "updateSupplierDataById"]);
    // Route::delete('datahub/b2b/contact/supplier-data/id/{id}', [NewContactDataController::class, "deleteSupplierDataById"]);
    // Route::get('datahub/b2b/contact/top-five-area-pharmacies',[NewContactDataController::class,"topFiveAreaPharmacies"]);
    // Route::get('datahub/b2b/contact/top-five-purchase-pharmacies', [NewContactDataController::class, "topFivePurchasePharmacies"]);
    // Route::get('datahub/b2b/contact/contact-growth', [NewContactDataController::class, "contactGrowthB2B"]);
    // Route::get('datahub/b2b/contact/top-contact-card', [NewContactDataController::class, "topContactCardB2B"]);
    // Route::get('datahub/b2b/contact/top-five-pharmacies-by-db', [NewContactDataController::class, "topFivePharmaciesByDatabase"]);
    //Route::get('datahub/b2b/export', [NewContactDataController::class, "exportDataB2B"]);
    //  Route::get('datahub/b2b/contact/general-newsletter-data/all', [NewContactDataController::class, "allGeneralNewsletterData"]);
    // Route::get('datahub/b2b/contact/general-newsletter-data/id/{id}', [NewContactDataController::class, "generalNewsletterDataById"]);
    // Route::post('datahub/b2b/contact/general-newsletter-data/add', [NewContactDataController::class, "addGeneralNewsletterData"]);
    // Route::put('datahub/b2b/contact/general-newsletter-data/id/{id}', [NewContactDataController::class, "updateGeneralNewsletterDataById"]);
    // Route::delete('datahub/b2b/contact/general-newsletter-data/id/{id}', [NewContactDataController::class, "deleteGeneralNewsletterDataById"]);
    
});

Route::prefix('v1')->name('api.')->group(function () {
    Route::get('datahub/b2c/contact/contact-growth', [NewContactDataController::class, "contactGrowth"]);
    Route::get('datahub/b2c/contact/top-contact-card', [NewContactDataController::class, "topContactCard"]);
    Route::get('datahub/b2c/contact/top-five-area-community',[NewContactDataController::class,'topFiveAreaCommunity']);
    
    Route::get('datahub/b2c/contact/community-data/all', [NewContactDataController::class, "allCommunityData"]);
    Route::get('datahub/b2c/contact/community-data/id/{id}', [NewContactDataController::class, "communityDataById"]);
    Route::get('datahub/b2c/contact/community-data/add', [NewContactDataController::class, "addCommunityData"]);
    Route::put('datahub/b2c/contact/community-data/id/{id}', [NewContactDataController::class, "updateCommunityDataById"]);
    Route::delete('datahub/b2c/contact/community-data/id/{id}', [NewContactDataController::class, "deleteCommunityDataById"]);
    // Route::get('datahub/b2c/contact/subscriber-data/all', [NewContactDataController::class, "allSubscriberData"]);
    // Route::get('datahub/b2c/contact/subscriber-data/id/{id}', [NewContactDataController::class, "subscriberDataById"]);
    // Route::post('datahub/b2c/contact/subscriber-data/add', [NewContactDataController::class, "addSubscriberData"]);
    // Route::put('datahub/b2c/contact/subscriber-data/id/{id}', [NewContactDataController::class, "updateSubscriberDataById"]);
    // Route::delete('datahub/b2c/contact/subscriber-data/id/{id}', [NewContactDataController::class, "deleteSubscriberDataById"]);
    
    Route::get('datahub/b2c/contact/pharmacy-database/all', [NewContactDataController::class, "pharmacyDatabaseAll"]);
    Route::get('datahub/b2c/contact/pharmacy-database/parent/{parentId}', [NewContactDataController::class, "pharmacyDatabaseByParentId"]);
    Route::post('datahub/b2c/contact/pharmacy-database/add', [NewContactDataController::class, "addPharmacyDatabase"]);
    Route::get('datahub/b2c/contact/pharmacy-database/parent/{parentId}/id/{id}', [NewContactDataController::class, "pharmacyDatabaseByParentIdAndId"]);
    Route::put('datahub/b2c/contact/pharmacy-database/parent/{parentId}/id/{id}', [NewContactDataController::class, "updatePharmacyDatabaseByParentIdAndId"]);
    Route::delete('datahub/b2c/contact/pharmacy-database/parent/{parentId}/id/{id}', [NewContactDataController::class, "deletePharmacyDatabaseByParentIdAndId"]);
    
    Route::post('datahub/b2c/contact/document-upload', [FileProcessorController::class, "documentUpload"]);
    //Route::post('datahub/b2c/contact/minio-upload', [NewContactDataController::class, "minioUpload"]);
    Route::get('woocommerce/customers',[NewContactDataController::class,'woocommerceCustomers']);  

});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
   /*  Route::get('top-five-area-pharmacies',[NewContactDataController::class,"topFiveAreaPharmacies"]);
    Route::get('top-five-purchase-pharmacies', [NewContactDataController::class, "topFivePurchasePharmacies"]);
    Route::get('contact-growth', [NewContactDataController::class, "contactGrowth"]);
    Route::get('top-contact-card', [NewContactDataController::class, "topContactCard"]);
    Route::get("datahub/b2c/contact/pharmacy-data/all", [NewContactDataController::class, "allPharmacyData"]);
    Route::get("datahub/b2c/contact/pharmacy-data/id/{id}", [NewContactDataController::class, "pharmacyDataById"]);
    Route::post("datahub/b2c/contact/pharmacy-data/add", [NewContactDataController::class, "addPharmacyData"]);
    Route::put("datahub/b2c/contact/pharmacy-data/id/{id}", [NewContactDataController::class, "updatePharmacyDataById"]);
    Route::delete("datahub/b2c/contact/pharmacy-data/id/{id}", [NewContactDataController::class, "deletePharmacyDataById"]);
    Route::get('datahub/b2c/contact/supplier-data/all', [NewContactDataController::class, "allSupplierData"]);
    Route::get('datahub/b2c/contact/supplier-data/id/{id}', [NewContactDataController::class, "supplierDataById"]);
    Route::post('datahub/b2c/contact/supplier-data/add', [NewContactDataController::class, "addSupplierData"]);
    Route::put('datahub/b2c/contact/supplier-data/id/{id}', [NewContactDataController::class, "updateSupplierDataById"]);
    Route::delete('datahub/b2c/contact/supplier-data/id/{id}', [NewContactDataController::class, "deleteSupplierDataById"]);
    Route::get('datahub/b2c/contact/community-data/all', [NewContactDataController::class, "allCommunityData"]);
    Route::get('datahub/b2c/contact/community-data/id/{id}', [NewContactDataController::class, "communityDataById"]);
    Route::post('datahub/b2c/contact/community-data/add', [NewContactDataController::class, "addCommunityData"]);
    Route::put('datahub/b2c/contact/community-data/id/{id}', [NewContactDataController::class, "updateCommunityDataById"]);
    Route::delete('datahub/b2c/contact/community-data/id/{id}', [NewContactDataController::class, "deleteCommunityDataById"]);
    Route::get('datahub/b2c/contact/general-newsletter-data/all', [NewContactDataController::class, "allGeneralNewsletterData"]);
    Route::get('datahub/b2c/contact/general-newsletter-data/id/{id}', [NewContactDataController::class, "generalNewsletterDataById"]);
    Route::post('datahub/b2c/contact/general-newsletter-data/add', [NewContactDataController::class, "addGeneralNewsletterData"]);
    Route::put('datahub/b2c/contact/general-newsletter-data/id/{id}', [NewContactDataController::class, "updateGeneralNewsletterDataById"]);
    Route::delete('datahub/b2c/contact/general-newsletter-data/id/{id}', [NewContactDataController::class, "deleteGeneralNewsletterDataById"]);
    Route::get('datahub/b2c/contact/pharmacy-database/parent/{parentId}', [NewContactDataController::class, "pharmacyDatabaseByParentId"]);
    Route::post('datahub/b2c/contact/pharmacy-database/add', [NewContactDataController::class, "addPharmacyDatabase"]);
    Route::get('datahub/b2c/contact/pharmacy-database/parent/{parentId}/id/{id}', [NewContactDataController::class, "pharmacyDatabaseByParentIdAndId"]);
    Route::put('datahub/b2c/contact/pharmacy-database/parent/{parentId}/id/{id}', [NewContactDataController::class, "updatePharmacyDatabaseByParentIdAndId"]);
    Route::delete('datahub/b2c/contact/pharmacy-database/parent/{parentId}/id/{id}', [NewContactDataController::class, "deletePharmacyDatabaseByParentIdAndId"]);
    Route::post('datahub/b2c/contact/minio-upload', [NewContactDataController::class, "minioUpload"]);
    Route::get('woocommerce/customers',[NewContactDataController::class,'woocommerceCustomers']); */  

});
