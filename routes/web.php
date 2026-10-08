<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TontineController;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;

Route::middleware('auth')->scopeBindings()->group(function () {
    Route::get('tontines/{tontine}/reports', [ReportController::class, 'index'])->name('tontines.reports.index');
    Route::get('tontines/{tontine}/reports/tours/{cycle}', [ReportController::class, 'tour'])->name('tontines.reports.tour');
    Route::get('tontines/{tontine}/reports/rounds/{round}', [ReportController::class, 'round'])->whereNumber('round')->name('tontines.reports.round');
    Route::get('tontines/{tontine}/members', [MemberController::class, 'index'])->name('tontines.members.index');
    Route::post('tontines/{tontine}/members', [MemberController::class, 'store'])->name('tontines.members.store');
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

Route::middleware('auth')->group(function () {
    Route::get('tontines/{tontine}/payouts', [PayoutController::class, 'index'])->name('tontines.payouts.index');
    Route::post('tontines/{tontine}/cycles/{cycle}/payout', [PayoutController::class, 'store'])->name('tontines.cycles.payout.store');
});
use App\Http\Controllers\DashboardController;

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
    
Route::middleware('auth')->group(function () {
    Route::get('tontines/{tontine}/cycles', [CycleController::class, 'index'])->name('tontines.cycles.index');
    Route::post('tontines/{tontine}/cycles/generate', [CycleController::class, 'generate'])->name('tontines.cycles.generate');
    Route::delete('tontines/{tontine}/cycles', [CycleController::class, 'reset'])->name('tontines.cycles.reset');
    Route::post('tontines/{tontine}/cycles/restart', [CycleController::class, 'restart'])->name('tontines.cycles.restart');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::resource('tontines', TontineController::class);

    });
    use App\Http\Controllers\ContributionController;

Route::middleware('auth')->group(function () {
    Route::get('tontines/{tontine}/contributions', [ContributionController::class, 'index'])->name('tontines.contributions.index');
    Route::post('tontines/{tontine}/cycles/{cycle}/contributions', [ContributionController::class, 'store'])->name('tontines.cycles.contributions.store');
    Route::delete('tontines/{tontine}/cycles/{cycle}/contributions/{contribution}', [ContributionController::class, 'destroy'])->name('tontines.cycles.contributions.destroy');
});

require __DIR__.'/auth.php';
