<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsappNewsletter\Http\Controllers\WhatsappNewsletterController;

Route::middleware('auth:sanctum')->prefix('v1/wa-campaign')->group(function () {
    Route::get('newsletter', [WhatsappNewsletterController::class, 'getNewsletters']);
    Route::get('newsletter/overview', [WhatsappNewsletterController::class, 'getNewsletterOverview']);
    Route::get('newsletter/{newsletterId}', [WhatsappNewsletterController::class, 'getNewsletterById']);
    Route::post('newsletter', [WhatsappNewsletterController::class, 'store']);
    Route::patch('newsletter/{newsletterId}', [WhatsappNewsletterController::class, 'update']);
    Route::post('newsletter/savefilter', [WhatsappNewsletterController::class, 'saveFilter']);
    Route::get('newsletter/savedfilter', [WhatsappNewsletterController::class, 'getSavedFilters']);
    Route::get('newsletter/savedfilter/{id}', [WhatsappNewsletterController::class, 'getSavedFilterById']);
    Route::get('newsletter/filtered-contacts/{newsletterId}', [WhatsappNewsletterController::class, 'getFilteredContacts']);
    Route::get('newsletter/summary/{newsletterId}', [WhatsappNewsletterController::class, 'getSummary']);
    Route::delete('newsletter/{newsletterId}', [WhatsappNewsletterController::class, 'deleteNewsletter']);
    Route::get('newsletter/metrics/{newsletterId}', [WhatsappNewsletterController::class, 'getMetricsById']);
});
