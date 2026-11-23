<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Models\Review;

// Page d'accueil publique. Dans routes/web.php, SUPPRIMER l'ancienne ligne Route::get('/', ...) de Breeze,
// puis ajouter juste avant « require __DIR__.'/auth.php'; » :  require __DIR__.'/landing.php';
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    if (Schema::hasTable('reviews')) {
        $reviews = Review::approved()->with('user:id,name')->latest('id')->limit(3)->get();
        $summary = Review::publicSummary();
    } else {
        $reviews = collect();
        $summary = ['count' => 0, 'avg' => 0.0];
    }

    return view('landing', compact('reviews', 'summary'));
})->name('home');
