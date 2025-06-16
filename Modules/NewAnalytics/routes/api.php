<?php

use Illuminate\Support\Facades\Route;
use Modules\NewAnalytics\Http\Controllers\NewAnalyticsController;
use App\Http\Middleware\ValidateSignature;

Route::prefix('v1')->name('api.')->group(function () {
    Route::post('/analytics/generate-signature', [NewAnalyticsController::class, 'generateSignature']);
});


Route::middleware([ValidateSignature::class])->prefix('v1')->name('api.')->group(function () {
    Route::get('/analytics/analytics-monthly-visitor', [NewAnalyticsController::class,"analyticsMonthlyVisitor"])->name('analytics.monthly.visitor');
    Route::get('/analytics/analytics-bounce-rate', [NewAnalyticsController::class,"analyticsBounceRate"]);
    Route::get('/analytics/analytics-three-month-visitor', [NewAnalyticsController::class,"analyticsThreeMonthVisitor"]);
    Route::get('/analytics/analytics-thirty-day-visitor', [NewAnalyticsController::class,"analyticsThirtyDayVisitor"]);
    Route::get('/analytics/analytics-twentyfour-hour-visitor', [NewAnalyticsController::class,"analyticsTwentyFourHourVisitor"]);
    Route::get('/analytics/analytics-now-on-page', [NewAnalyticsController::class,"analyticsNowOnPage"]);
    Route::get('/analytics/analytics-total-user-registered', [NewAnalyticsController::class,"totalUserRegistered"]);
    Route::get('/analytics/analytics-total-seven-day-visitor', [NewAnalyticsController::class,"analyticsTotalSevenDayVisitor"]);
    Route::get('/analytics/analytics-total-seven-day-new-user',[NewAnalyticsController::class,"analyticsTotalSevenDayNewUser"]);
    Route::get('/analytics/analytics-total-thirty-day-visitor',[NewAnalyticsController::class,"analyticsTotalThirtyDayVisitor"]);
    Route::get('/analytics/analytics-total-thirty-day-new-user',[NewAnalyticsController::class,"analyticsTotalThirtyDayNewUser"]);
    Route::get('/analytics/analytics-total-seven-day-likes',[NewAnalyticsController::class,"analyticsTotalSevenDayLikes"]);
    Route::get('/analytics/analytics-total-seven-day-saves',[NewAnalyticsController::class,"analyticsTotalSevenDaySaves"]);
    Route::get('/analytics/analytics-total-seven-day-comments',[NewAnalyticsController::class,"analyticsTotalSevenDayComments"]);
    Route::get('/analytics/analytics-total-thirty-day-likes',[NewAnalyticsController::class,"analyticsTotalThirtyDayLikes"]);
    Route::get('/analytics/analytics-total-thirty-day-saves',[NewAnalyticsController::class,"analyticsTotalThirtyDaySaves"]);
    Route::get('/analytics/analytics-total-thirty-day-comments',[NewAnalyticsController::class,"analyticsTotalThirtyDayComments"]);
    Route::get('/analytics/analytics-total-twenty-four-hour-likes',[NewAnalyticsController::class,"analyticsTotalTwentyFourHourLikes"]);
    Route::get('/analytics/analytics-total-twenty-four-hour-saves',[NewAnalyticsController::class,"analyticsTotalTwentyFourHourSaves"]);
    Route::get('/analytics/analytics-total-twenty-four-hour-comments',[NewAnalyticsController::class,"analyticsTotalTwentyFourHourComments"]);
    Route::get('/analytics/analytics-total-twenty-four-hour-new-user',[NewAnalyticsController::class,"analyticsTotalTwentyFourHourNewUser"]);
    Route::get('/analytics/analytics-total-twenty-four-hour-visitor',[NewAnalyticsController::class,"analyticsTotalTwentyFourHourVisitor"]);
    Route::get('/analytics/analytics-average-time-onsite',[NewAnalyticsController::class,"analyticsAverageTimeOnsite"]);
    Route::get('/analytics/analytics-twentyfour-hour-yesterday-visitor',[NewAnalyticsController::class,"analyticsTwentyFourHourYesterdayVisitor"]);
    Route::get('/analytics/analytics-twentyfour-hour-yesterday-new-user',[NewAnalyticsController::class,"analyticsTwentyFourHourYesterdayNewUser"]);
    Route::get('/analytics/analytics-select-date-visitor',[NewAnalyticsController::class,"analyticsSelectDateVisitor"]);
    Route::get('/analytics/analytics-select-date-new-user',[NewAnalyticsController::class,"analyticsSelectDateNewUser"]);
    });

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    /* Route::get('analytics-monthly-visitor', [NewAnalyticsController::class,"analyticsMonthlyVisitor"])->name('analytics.monthly.visitor');
    Route::get('analytics-bounce-rate', [NewAnalyticsController::class,"analyticsBounceRate"]);
    Route::get('analytics-three-month-visitor', [NewAnalyticsController::class,"analyticsThreeMonthVisitor"]);
    Route::get('analytics-thirty-day-visitor', [NewAnalyticsController::class,"analyticsThirtyDayVisitor"]);
    Route::get('analytics-twentyfour-hour-visitor', [NewAnalyticsController::class,"analyticsTwentyFourHourVisitor"]);
    Route::get('analytics-now-on-page', [NewAnalyticsController::class,"analyticsNowOnPage"]);
    Route::get('analytics-total-user-registered', [NewAnalyticsController::class,"totalUserRegistered"]);
    Route::get('analytics-total-seven-day-visitor', [NewAnalyticsController::class,"analyticsTotalSevenDayVisitor"]);
    Route::get('analytics-total-seven-day-new-user',[NewAnalyticsController::class,"analyticsTotalSevenDayNewUser"]);
    Route::get('analytics-total-thirty-day-visitor',[NewAnalyticsController::class,"analyticsTotalThirtyDayVisitor"]);
    Route::get('analytics-total-thirty-day-new-user',[NewAnalyticsController::class,"analyticsTotalThirtyDayNewUser"]);
    Route::get('analytics-total-seven-day-likes',[NewAnalyticsController::class,"analyticsTotalSevenDayLikes"]);
    Route::get('analytics-total-seven-day-saves',[NewAnalyticsController::class,"analyticsTotalSevenDaySaves"]);
    Route::get('analytics-total-seven-day-comments',[NewAnalyticsController::class,"analyticsTotalSevenDayComments"]);
    Route::get('analytics-total-thirty-day-likes',[NewAnalyticsController::class,"analyticsTotalThirtyDayLikes"]);
    Route::get('analytics-total-thirty-day-saves',[NewAnalyticsController::class,"analyticsTotalThirtyDaySaves"]);
    Route::get('analytics-total-thirty-day-comments',[NewAnalyticsController::class,"analyticsTotalThirtyDayComments"]);
    Route::get('analytics-total-twenty-four-hour-likes',[NewAnalyticsController::class,"analyticsTotalTwentyFourHourLikes"]);
    Route::get('analytics-total-twenty-four-hour-saves',[NewAnalyticsController::class,"analyticsTotalTwentyFourHourSaves"]);
    Route::get('analytics-total-twenty-four-hour-comments',[NewAnalyticsController::class,"analyticsTotalTwentyFourHourComments"]);
    Route::get('analytics-total-twenty-four-hour-new-user',[NewAnalyticsController::class,"analyticsTotalTwentyFourHourNewUser"]);
    Route::get('analytics-total-twenty-four-hour-visitor',[NewAnalyticsController::class,"analyticsTotalTwentyFourHourVisitor"]);
    Route::get('analytics-average-time-onsite',[NewAnalyticsController::class,"analyticsAverageTimeOnsite"]);
    Route::get('analytics-twentyfour-hour-yesterday-visitor',[NewAnalyticsController::class,"analyticsTwentyFourHourYesterdayVisitor"]);
    Route::get('analytics-twentyfour-hour-yesterday-new-user',[NewAnalyticsController::class,"analyticsTwentyFourHourYesterdayNewUser"]); */
});
