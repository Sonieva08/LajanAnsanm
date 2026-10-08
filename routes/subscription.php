<?php

use App\Http\Controllers\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('plans', [SubscriptionController::class, 'plans'])->name('plans');
Route::get('subscription', [SubscriptionController::class, 'show'])->name('subscription');
Route::get('plans/{plan}/request', [SubscriptionController::class, 'requestForm'])->name('plans.request');
Route::post('plans/{plan}/request', [SubscriptionController::class, 'submitRequest'])->name('plans.request.submit');
