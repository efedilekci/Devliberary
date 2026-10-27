<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'session.version'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::post('resources/bulk', [ResourceController::class, 'bulk'])->name('resources.bulk');
    Route::patch('resources/{resource}/favorite', [ResourceController::class, 'favorite'])->name('resources.favorite');
    Route::patch('resources/{resource}/archive', [ResourceController::class, 'archive'])->name('resources.archive');
    Route::delete('resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');
    Route::post('collections', [CollectionController::class, 'store'])->name('collections.store');
    Route::patch('collections/{collection}/pin', [CollectionController::class, 'pin'])->name('collections.pin');
    Route::patch('collections/{collection}/collapse', [CollectionController::class, 'collapse'])->name('collections.collapse');
    Route::patch('collections/{collection}/move', [CollectionController::class, 'move'])->name('collections.move');
    Route::get('collections/{collection}', [CollectionController::class, 'show'])->name('collections.show');
});

require __DIR__.'/settings.php';
