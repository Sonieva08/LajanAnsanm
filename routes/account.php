<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::post('profil/preferans', [AccountController::class, 'preferences'])->name('profile.preferences');
    Route::get('done-mwen', [AccountController::class, 'export'])->name('account.export');
    Route::delete('kont', [AccountController::class, 'destroy'])->name('account.destroy');
});
