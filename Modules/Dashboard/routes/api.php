<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Http\Controllers\DashboardController;

Route::middleware(['auth:sanctum'])->group(function () {
  //  Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/charts', [DashboardController::class, 'charts'])->name('dashboard.charts');
    Route::get('/dashboard/charts-circle', [DashboardController::class, 'charts_circle'])->name('dashboard.charts');
});
 
Route::get('/dashboard', [DashboardController::class, 'index']);
