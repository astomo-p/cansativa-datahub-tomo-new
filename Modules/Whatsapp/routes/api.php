<?php

use Illuminate\Support\Facades\Route;
use Modules\Whatsapp\Http\Controllers\WhatsappMessageController;
use Modules\Whatsapp\Http\Controllers\WhatsappWebhookController;
use Modules\Whatsapp\Http\Controllers\WhatsappTemplateController;
use Modules\Whatsapp\Http\Controllers\WhatsappAssignmentController;
use Modules\Whatsapp\Http\Controllers\MessageFilterController;
use Modules\Whatsapp\Http\Controllers\WhatsappChatTemplateController;

Route::group(['prefix' => 'v1/whatsapp'], function () {
    Route::middleware('auth:sanctum')->group(function () {
        // Message routes
        Route::get('messages', [WhatsappMessageController::class, 'getMessageData']);
        Route::get('chats/{contactId}', [WhatsappMessageController::class, 'getMessageDetailsByContact']);
        Route::post('send-text', [WhatsappMessageController::class, 'sendText']);
        Route::post('send-template', [WhatsappMessageController::class, 'sendTemplate']);

        // Chat Template routes
        Route::get('chat-templates', [WhatsappChatTemplateController::class, 'index']);
        Route::post('chat-templates', [WhatsappChatTemplateController::class, 'store']);
        // Route::get('chat-templates/{id}', [WhatsappChatTemplateController::class, 'show']);
        // Route::put('chat-templates/{id}', [WhatsappChatTemplateController::class, 'update']);
        Route::delete('chat-templates/{id}', [WhatsappChatTemplateController::class, 'destroy']);

        // Agents routes
        Route::get('agents', [WhatsappAssignmentController::class, 'getAgents']);
        Route::post('assign', [WhatsappAssignmentController::class, 'assignContactToAgent']);
        Route::put('status', [WhatsappAssignmentController::class, 'updateStatus']);

        // Message Filter routes
        Route::get('message-filters', [MessageFilterController::class, 'index']);
        Route::post('message-filters', [MessageFilterController::class, 'store']);
        Route::put('message-filters/{id}', [MessageFilterController::class, 'update']);
        Route::delete('message-filters/{id}', [MessageFilterController::class, 'destroy']);
    });

    // Webhook routes
    Route::get('webhook', [WhatsappWebhookController::class, 'verify']);
    Route::post('webhook', [WhatsappWebhookController::class, 'handle']);
});

// Campaign Template routes
Route::middleware('auth:sanctum')->prefix('v1/wa-campaign')->group(function () {
    // Template Creation
    Route::get('templates', [WhatsappTemplateController::class, 'getTemplates']);
    Route::post('templates', [WhatsappTemplateController::class, 'createTemplate']);
    Route::post('templates/update/{templateId}', [WhatsappTemplateController::class, 'updateTemplate']);
    Route::post('templates/submit', [WhatsappTemplateController::class, 'submitTemplate']);
    Route::delete('templates', [WhatsappTemplateController::class, 'deleteTemplate']);
    Route::put('templates/archive/{id}', [WhatsappTemplateController::class, 'archiveTemplate']);
    Route::post('templates/duplicate', [WhatsappTemplateController::class, 'duplicateTemplate']);

    // User Attributes route
    Route::get('user-attributes', [WhatsappTemplateController::class, 'getUserAttributes']);
});
