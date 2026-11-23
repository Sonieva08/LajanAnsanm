<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Controllers\Admin\HistoryController;
use App\Http\Controllers\Admin\HelpController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SecurityController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SubscriptionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\EnsureOwner;
use App\Http\Middleware\EnsureTwoFactor;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware([EnsureOwner::class, 'verified'])->group(function () {
    // Vérification du code 2 étapes : accessible avant d'être vérifié
    Route::get('security/verify', [SecurityController::class, 'verifyForm'])->name('security.verify');
    Route::post('security/verify', [SecurityController::class, 'verify'])->middleware('throttle:10,1')->name('security.verify.submit');

    Route::middleware(EnsureTwoFactor::class)->group(function () {
        Route::get('/', OverviewController::class)->name('overview');

        Route::get('users', [UserController::class, 'index'])->name('users');
        Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::post('users/{user}/subscription', [UserController::class, 'grant'])->name('users.grant');
        Route::delete('users/{user}/subscription', [UserController::class, 'revoke'])->name('users.revoke');

        Route::get('plans', [PlanController::class, 'index'])->name('plans');
        Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions');
        Route::get('plans/payment', [PlanController::class, 'payment'])->name('plans.payment');
        Route::get('plans/create', [PlanController::class, 'create'])->name('plans.create');
        Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
        Route::post('plans/rules', [PlanController::class, 'rules'])->name('plans.rules');
        Route::get('plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
        Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
        Route::post('plans/{plan}/toggle', [PlanController::class, 'toggle'])->name('plans.toggle');
        Route::delete('plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');

        Route::get('requests', [RequestController::class, 'index'])->name('requests');
        Route::get('requests/{subscriptionRequest}/receipt', [RequestController::class, 'receipt'])->name('requests.receipt');
        Route::post('requests/{subscriptionRequest}/approve', [RequestController::class, 'approve'])->name('requests.approve');
        Route::post('requests/{subscriptionRequest}/reject', [RequestController::class, 'reject'])->name('requests.reject');

        Route::get('activity', [ActivityController::class, 'index'])->name('activity');
        Route::get('history', [HistoryController::class, 'index'])->name('history');
        Route::get('help', [HelpController::class, 'index'])->name('help');

        Route::get('announcements', [AnnouncementController::class, 'index'])->name('announcements');
        Route::post('announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::post('announcements/{announcement}/toggle', [AnnouncementController::class, 'toggle'])->name('announcements.toggle');
        Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews');
        Route::post('reviews/{review}/approve', [ReviewController::class, 'approve'])->name('reviews.approve');
        Route::post('reviews/{review}/hide', [ReviewController::class, 'hide'])->name('reviews.hide');
        Route::delete('reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings');
        Route::post('settings/maintenance', [SettingsController::class, 'maintenance'])->name('settings.maintenance');
        Route::get('settings/security', [SecurityController::class, 'show'])->name('settings.security');
        Route::post('settings/security/setup', [SecurityController::class, 'setup'])->name('settings.security.setup');
        Route::post('settings/security/confirm', [SecurityController::class, 'confirm'])->name('settings.security.confirm');
        Route::post('settings/security/disable', [SecurityController::class, 'disable'])->name('settings.security.disable');
        Route::get('settings/notifications', [NotificationController::class, 'show'])->name('settings.notifications');
        Route::post('settings/notifications', [NotificationController::class, 'update'])->name('settings.notifications.update');
    });
});
