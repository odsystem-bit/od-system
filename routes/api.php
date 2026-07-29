<?php

use Illuminate\Support\Facades\Route;

Route::prefix('bot')->middleware('bot.apikey')->group(function () {
    Route::post('/vendors', [\App\Http\Controllers\Api\Bot\VendorController::class, 'store']);
    Route::post('/products', [\App\Http\Controllers\Api\Bot\ProductController::class, 'store']);
    Route::get('/vendors/{phone}', [\App\Http\Controllers\Api\Bot\VendorController::class, 'findByPhone']);

    // Commandes depuis Tracy
    Route::post('/orders', [\App\Http\Controllers\Api\Bot\BotOrderController::class, 'store']);
    Route::get('/orders/{reference}', [\App\Http\Controllers\Api\Bot\BotOrderController::class, 'show']);
    Route::get('/vendors/{vendorId}/stats', [\App\Http\Controllers\Api\Bot\BotOrderController::class, 'vendorStats']);
});

// Webhook public Moneroo (pas de clé API)
Route::post('/webhook/moneroo', [\App\Http\Controllers\Webhook\MonerooWebhookController::class, 'handle']);
