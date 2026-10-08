<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\RequestController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Middleware\EnsureOwner;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(EnsureOwner::class)->group(function () {
    Route::get('/', OverviewController::class)->name('overview');

    Route::get('users', [UserController::class, 'index'])->name('users');
    Route::post('users/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
    Route::post('users/{user}/subscription', [UserController::class, 'grant'])->name('users.grant');
    Route::delete('users/{user}/subscription', [UserController::class, 'revoke'])->name('users.revoke');

    Route::get('plans', [PlanController::class, 'index'])->name('plans');
    Route::get('plans/create', [PlanController::class, 'create'])->name('plans.create');
    Route::post('plans', [PlanController::class, 'store'])->name('plans.store');
    Route::post('plans/rules', [PlanController::class, 'rules'])->name('plans.rules');
    Route::get('plans/{plan}/edit', [PlanController::class, 'edit'])->name('plans.edit');
    Route::put('plans/{plan}', [PlanController::class, 'update'])->name('plans.update');
    Route::post('plans/{plan}/toggle', [PlanController::class, 'toggle'])->name('plans.toggle');
    Route::delete('plans/{plan}', [PlanController::class, 'destroy'])->name('plans.destroy');

    Route::get('requests', [RequestController::class, 'index'])->name('requests');
    Route::post('requests/{subscriptionRequest}/approve', [RequestController::class, 'approve'])->name('requests.approve');
    Route::post('requests/{subscriptionRequest}/reject', [RequestController::class, 'reject'])->name('requests.reject');

    Route::get('activity', [ActivityController::class, 'index'])->name('activity');
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('settings/maintenance', [SettingsController::class, 'maintenance'])->name('settings.maintenance');
});
