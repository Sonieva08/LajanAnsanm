<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// À ajouter dans routes/web.php, juste avant : require __DIR__.'/auth.php';
// Et SUPPRIMER dans routes/web.php l'ancienne ligne : Route::view('konfidansyalite', 'legal.privacy')->name('privacy');
Route::get('konfidansyalite', fn () => view('legal.page', [
    'title' => 'Politik konfidansyalite',
    'html' => Str::markdown(File::get(resource_path('legal/privacy.md'))),
]))->name('privacy');

Route::get('kondisyon', fn () => view('legal.page', [
    'title' => 'Kondisyon Jeneral Itilizasyon',
    'html' => Str::markdown(File::get(resource_path('legal/terms.md'))),
]))->name('terms');
