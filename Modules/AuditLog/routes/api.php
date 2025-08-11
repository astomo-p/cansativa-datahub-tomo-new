<?php

use Illuminate\Support\Facades\Route;
use Modules\AuditLog\Http\Controllers\AuditLogController;

Route::prefix('v1')->name('api.')->group(function () {
    Route::get('datahub/audit-logs', [AuditLogController::class, "getAllAuditLogs"]);
    Route::get('datahub/audit-logs/filter', [AuditLogController::class, "getAuditFilter"]);
    Route::get('datahub/contact-logs', [AuditLogController::class, "getAllContactLogs"]);
    Route::get('datahub/contact-logs/{contact_id}', [AuditLogController::class, "getContactLogDetail"]);
});

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
});
