<?php

use Illuminate\Support\Facades\Route;
use Soluta\Subscription\Http\Controllers\Api\SubscriptionController;
use Soluta\Subscription\Http\Controllers\Api\WebhookController;

Route::post('/api/webhooks/stripe', [WebhookController::class, 'stripeWebhookHandle']);

Route::prefix('api')->middleware(['auth:sanctum'])->group(function () {
    Route::post('subscribe', [SubscriptionController::class, 'subscribe']);
});
