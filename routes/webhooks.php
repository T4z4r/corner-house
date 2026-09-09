<?php

use App\Http\Controllers\Webhooks\Beds24WebhookController;
use App\Http\Controllers\Webhooks\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/stripe', [StripeWebhookController::class, '__invoke'])->name('webhooks.stripe');
Route::post('/beds24', [Beds24WebhookController::class, '__invoke'])->name('webhooks.beds24');
