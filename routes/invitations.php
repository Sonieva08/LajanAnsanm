<?php

use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MemberPortalController;
use Illuminate\Support\Facades\Route;

// À ajouter dans routes/web.php, juste avant : require __DIR__.'/auth.php';
Route::middleware('auth')->group(function () {
    Route::get('tontin/{tontine}/envite', [InvitationController::class, 'create'])->name('invitations.create');
    Route::post('tontin/{tontine}/envitasyon', [InvitationController::class, 'store'])->name('invitations.store');
    Route::get('mes-tontines', [InvitationController::class, 'mine'])->name('memberships.index');
    Route::get('mes-tontines/{member}/portail', [MemberPortalController::class, 'enterForAccount'])
        ->whereNumber('member')->name('memberships.portal');
});
Route::get('envitasyon/{token}', [InvitationController::class, 'show'])->middleware('throttle:30,1')->name('invitations.show');
Route::post('envitasyon/{token}/aksepte', [InvitationController::class, 'accept'])
    ->middleware('throttle:20,1')->name('invitations.accept');
Route::get('envitasyon-rejwenn/{token}', [InvitationController::class, 'joined'])
    ->middleware('throttle:30,1')->name('invitations.joined');
