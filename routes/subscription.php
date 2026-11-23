<?php

use App\Http\Controllers\OnlinePaymentController;
use App\Http\Controllers\PaymentQrController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('plans', [SubscriptionController::class, 'plans'])->name('plans');
Route::get('subscription', [SubscriptionController::class, 'show'])->name('subscription');
Route::get('plans/{plan}/request', [SubscriptionController::class, 'requestForm'])->name('plans.request');
Route::post('plans/{plan}/request', [SubscriptionController::class, 'submitRequest'])->name('plans.request.submit');
Route::get('subscription/payment-qr/{method}', [PaymentQrController::class, 'subscription'])->name('subscription.payment-qr');
Route::get('subscription/requests/{subscriptionRequest}/receipt', [SubscriptionController::class, 'receipt'])->name('subscription.requests.receipt');

// Paiement en ligne (MonCash via MonCashConnect)
Route::post('plans/{plan}/pay-online', [OnlinePaymentController::class, 'start'])->name('plans.pay-online');
Route::get('subscription/payment/{reference}/return', [OnlinePaymentController::class, 'returned'])->name('subscription.online.return');
Route::get('subscription/payment/{reference}/status', [OnlinePaymentController::class, 'status'])->middleware('throttle:40,1')->name('subscription.online.status');
