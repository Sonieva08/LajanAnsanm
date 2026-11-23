<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TontineController;


Route::post('webhooks/moncashconnect', [\App\Http\Controllers\OnlinePaymentController::class, 'webhook'])
    ->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
    ->name('webhooks.moncashconnect');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberInvitationController;
use App\Http\Controllers\ReportController;

Route::middleware(['auth', 'verified'])->scopeBindings()->group(function () {
    Route::get('tontines/{tontine}/reports', [ReportController::class, 'index'])->name('tontines.reports.index');
    Route::get('tontines/{tontine}/reports/tours/{cycle}', [ReportController::class, 'tour'])->name('tontines.reports.tour');
    Route::get('tontines/{tontine}/reports/rounds/{round}', [ReportController::class, 'round'])->whereNumber('round')->name('tontines.reports.round');
    Route::get('tontines/{tontine}/members', [MemberController::class, 'index'])->name('tontines.members.index');
    Route::post('tontines/{tontine}/members', [MemberController::class, 'store'])->name('tontines.members.store');
    Route::post('tontines/{tontine}/members/invitations', [MemberController::class, 'invite'])->name('tontines.members.invite');
    Route::post('tontines/{tontine}/members/shuffle', [MemberController::class, 'shuffle'])->name('tontines.members.shuffle');
    Route::put('tontines/{tontine}/members/{member}', [MemberController::class, 'update'])->name('tontines.members.update');
    Route::delete('tontines/{tontine}/members/{member}', [MemberController::class, 'destroy'])->name('tontines.members.destroy');
    Route::post('tontines/{tontine}/members/{member}/move/{direction}', [MemberController::class, 'move'])->name('tontines.members.move');
    Route::post('tontines/{tontine}/members/{member}/reactivate', [MemberController::class, 'reactivate'])->name('tontines.members.reactivate');
});

Route::get('/lang/{locale}', function (string $locale) {
    abort_unless(in_array($locale, ['ht', 'fr'], true), 404);
    session(['locale' => $locale]);
    return back();
})->name('lang.switch');
use App\Http\Controllers\CycleController;

use App\Http\Controllers\PayoutController;

Route::middleware(['auth', 'verified'])->scopeBindings()->group(function () {
    Route::get('tontines/{tontine}/payouts', [PayoutController::class, 'index'])->name('tontines.payouts.index');
    Route::post('tontines/{tontine}/cycles/{cycle}/payout', [PayoutController::class, 'store'])->name('tontines.cycles.payout.store');
    Route::get('tontines/{tontine}/cycles/{cycle}/payout/receipt', [PayoutController::class, 'receipt'])->name('tontines.cycles.payout.receipt');
    Route::post('tontines/{tontine}/cycles/{cycle}/payout/reopen', [PayoutController::class, 'reopen'])->name('tontines.cycles.payout.reopen');
});
use App\Http\Controllers\DashboardController;

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
    
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tontines/{tontine}/cycles', [CycleController::class, 'index'])->name('tontines.cycles.index');
    Route::post('tontines/{tontine}/cycles/generate', [CycleController::class, 'generate'])->name('tontines.cycles.generate');
    Route::delete('tontines/{tontine}/cycles', [CycleController::class, 'reset'])->name('tontines.cycles.reset');
    Route::post('tontines/{tontine}/cycles/restart', [CycleController::class, 'restart'])->name('tontines.cycles.restart');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/email/resend', [ProfileController::class, 'resendEmailChange'])->name('profile.email.resend');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('tontines', TontineController::class);
});
    use App\Http\Controllers\ContributionController;

Route::middleware(['auth', 'verified'])->scopeBindings()->group(function () {
    Route::get('tontines/{tontine}/contributions', [ContributionController::class, 'index'])->name('tontines.contributions.index');
    Route::post('tontines/{tontine}/cycles/{cycle}/contributions', [ContributionController::class, 'store'])->name('tontines.cycles.contributions.store');
    Route::delete('tontines/{tontine}/cycles/{cycle}/contributions/{contribution}', [ContributionController::class, 'destroy'])->name('tontines.cycles.contributions.destroy');
});


use App\Http\Controllers\MemberPortalController;
use App\Http\Controllers\PortalSettingsController;
use App\Http\Controllers\DeclarationController;
use App\Http\Controllers\PaymentQrController;

// Côté créateur : réglages du portail membre
Route::middleware(['auth', 'verified'])->scopeBindings()->group(function () {
    Route::get('tontines/{tontine}/portal', [PortalSettingsController::class, 'edit'])->name('tontines.portal.edit');
    Route::put('tontines/{tontine}/portal', [PortalSettingsController::class, 'update'])->name('tontines.portal.update');
    Route::get('tontines/{tontine}/payment-qr/{method}', [PaymentQrController::class, 'tontine'])->name('tontines.payment-qr');
    Route::post('tontines/{tontine}/portal/links', [PortalSettingsController::class, 'links'])->name('tontines.portal.links');
    Route::post('tontines/{tontine}/portal/members/{member}/link', [PortalSettingsController::class, 'link'])->name('tontines.portal.link');
});

// Côté créateur : valider ou refuser les paiements déclarés par les membres
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('tontines/{tontine}/declarations', [DeclarationController::class, 'index'])->name('tontines.declarations.index');
    Route::get('tontines/{tontine}/declarations/{declaration}/receipt', [DeclarationController::class, 'receipt'])->scopeBindings()->name('tontines.declarations.receipt');
    Route::post('tontines/{tontine}/declarations/{declaration}/approve', [DeclarationController::class, 'approve'])->name('tontines.declarations.approve');
    Route::post('tontines/{tontine}/declarations/{declaration}/reject', [DeclarationController::class, 'reject'])->name('tontines.declarations.reject');
});

// Côté membre : lien personnel, puis session (pas de compte, pas d'e-mail)
Route::get('/invitation/{token}', [MemberInvitationController::class, 'show'])
    ->middleware(['throttle:portal.token', \App\Http\Middleware\PortalHeaders::class])
    ->name('member.invitation.show');
Route::post('/invitation/{token}', [MemberInvitationController::class, 'accept'])
    ->middleware(['throttle:portal.token', \App\Http\Middleware\PortalHeaders::class])
    ->name('member.invitation.accept');
Route::get('/m/{token}', [MemberPortalController::class, 'enter'])->middleware(['throttle:30,1', \App\Http\Middleware\PortalHeaders::class])->name('portal.enter');
Route::middleware(['member.portal', \App\Http\Middleware\PortalHeaders::class])->group(function () {
    Route::get('/portal', [MemberPortalController::class, 'home'])->name('portal.home');
    Route::get('/portal/payment-qr/{method}', [PaymentQrController::class, 'memberPortal'])->name('portal.payment-qr');
    Route::post('/portal/cycles/{cycle}/declare', [MemberPortalController::class, 'declare'])->name('portal.declare');
    Route::get('/portal/declarations/{declaration}/receipt', [MemberPortalController::class, 'receipt'])->name('portal.declarations.receipt');
    Route::post('/portal/payout-destination', [MemberPortalController::class, 'payoutDestination'])->name('portal.payout.destination');
    Route::get('/portal/payouts/{payout}/receipt', [MemberPortalController::class, 'payoutReceipt'])->name('portal.payout.receipt');
    Route::post('/portal/payouts/{payout}/confirm', [MemberPortalController::class, 'confirmPayout'])->name('portal.payout.confirm');
    Route::post('/portal/payouts/{payout}/dispute', [MemberPortalController::class, 'disputePayout'])->name('portal.payout.dispute');
    Route::post('/portal/logout', [MemberPortalController::class, 'logout'])->name('portal.logout');
});

Route::get('auth/google', [\App\Http\Controllers\GoogleAuthController::class, 'redirect'])->middleware('guest')->name('google.redirect');
Route::get('auth/google/callback', [\App\Http\Controllers\GoogleAuthController::class, 'callback'])->middleware(['guest', 'throttle:20,1'])->name('google.callback');

Route::middleware('auth')->group(function () {
    Route::get('kont/modpas', [\App\Http\Controllers\PasswordController::class, 'edit'])->name('password.set');
    Route::post('kont/modpas', [\App\Http\Controllers\PasswordController::class, 'update'])->name('password.set.update');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::delete('notifications/read', [NotificationController::class, 'clearRead'])->name('notifications.clear-read');
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::delete('notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
});

require __DIR__.'/account.php';

require __DIR__.'/landing.php';

require __DIR__.'/reviews.php';

require __DIR__.'/invitations.php';

require __DIR__.'/legal.php';

require __DIR__.'/auth.php';
