<?php

use App\Http\Controllers\HelpController;
use Illuminate\Support\Facades\Route;

// Public (accessible sur tous les écrans, y compris portail membre et visiteurs) mais limité : 60 requêtes par minute et par adresse IP
Route::post('help/log', [HelpController::class, 'log'])->middleware('throttle:60,1')->name('help.log');
Route::post('help/ask', [HelpController::class, 'ask'])->middleware('throttle:60,1')->name('help.ask');
