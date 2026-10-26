<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'session.version'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::post('collections', [CollectionController::class, 'store'])->name('collections.store');
});

require __DIR__.'/settings.php';
