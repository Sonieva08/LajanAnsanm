<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Affiche les textes dans la langue active de l'application.
Route::get('konfidansyalite', fn () => view('legal.page', [
    'title' => app()->getLocale() === 'ht' ? 'Politik Konfidansyalite' : 'Politique de confidentialité',
    'html' => Str::markdown(File::get(resource_path('legal/privacy'.(app()->getLocale() === 'ht' ? '.ht' : '').'.md'))),
]))->name('privacy');

Route::get('kondisyon', fn () => view('legal.page', [
    'title' => app()->getLocale() === 'ht' ? 'Kondisyon Jeneral Itilizasyon' : "Conditions générales d'utilisation",
    'html' => Str::markdown(File::get(resource_path('legal/terms'.(app()->getLocale() === 'ht' ? '.ht' : '').'.md'))),
]))->name('terms');
