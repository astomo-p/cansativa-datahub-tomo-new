<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Analytics\App\Http\Controllers\AnalyticsController;

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
    Route::get('analytics-monthly-visitor', [AnalyticsController::class,"analyticsMonthlyVisitor"])->name('analytics.monthly.visitor');
    Route::get('analytics-bounce-rate', [AnalyticsController::class,"analyticsBounceRate"]);
    Route::get('analytics-three-month-visitor', [AnalyticsController::class,"analyticsThreeMonthVisitor"]);
    Route::get('analytics-thirty-day-visitor', [AnalyticsController::class,"analyticsThirtyDayVisitor"]);
    Route::get('analytics-twentyfour-hour-visitor', [AnalyticsController::class,"analyticsTwentyFourHourVisitor"]);
    Route::get('analytics-now-on-page', [AnalyticsController::class,"analyticsNowOnPage"]);
    Route::get('analytics-total-user-registered', [AnalyticsController::class,"totalUserRegistered"]);
    Route::get('analytics-total-seven-day-visitor', [AnalyticsController::class,"analyticsTotalSevenDayVisitor"]);
    Route::get('analytics-total-seven-day-new-user',[AnalyticsController::class,"analyticsTotalSevenDayNewUser"]);
    Route::get('analytics-total-thirty-day-visitor',[AnalyticsController::class,"analyticsTotalThirtyDayVisitor"]);
    Route::get('analytics-total-thirty-day-new-user',[AnalyticsController::class,"analyticsTotalThirtyDayNewUser"]);
    Route::get('analytics-total-seven-day-likes',[AnalyticsController::class,"analyticsTotalSevenDayLikes"]);
    Route::get('analytics-total-seven-day-saves',[AnalyticsController::class,"analyticsTotalSevenDaySaves"]);
    Route::get('analytics-total-seven-day-comments',[AnalyticsController::class,"analyticsTotalSevenDayComments"]);
    Route::get('analytics-total-thirty-day-likes',[AnalyticsController::class,"analyticsTotalThirtyDayLikes"]);
    Route::get('analytics-total-thirty-day-saves',[AnalyticsController::class,"analyticsTotalThirtyDaySaves"]);
    Route::get('analytics-total-thirty-day-comments',[AnalyticsController::class,"analyticsTotalThirtyDayComments"]);
    Route::get('analytics-total-twenty-four-hour-likes',[AnalyticsController::class,"analyticsTotalTwentyFourHourLikes"]);
    Route::get('analytics-total-twenty-four-hour-saves',[AnalyticsController::class,"analyticsTotalTwentyFourHourSaves"]);
    Route::get('analytics-total-twenty-four-hour-comments',[AnalyticsController::class,"analyticsTotalTwentyFourHourComments"]);
    Route::get('analytics-total-twenty-four-hour-new-user',[AnalyticsController::class,"analyticsTotalTwentyFourHourNewUser"]);
    Route::get('analytics-total-twenty-four-hour-visitor',[AnalyticsController::class,"analyticsTotalTwentyFourHourVisitor"]);
});

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('analytics', fn (Request $request) => $request->user())->name('analytics');
});
