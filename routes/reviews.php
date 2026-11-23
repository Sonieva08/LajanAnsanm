<?php

use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

// Avis des utilisateurs : lecture publique et écriture réservée aux comptes connectés
Route::get('/avis', [ReviewController::class, 'index'])->name('reviews.index');

Route::middleware('auth')->group(function () {
    Route::post('/avis', [ReviewController::class, 'store'])->middleware('throttle:6,1')->name('reviews.store');
    Route::delete('/avis', [ReviewController::class, 'destroy'])->name('reviews.destroy');
});
