<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\SnippetController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'session.version'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('resources', [ResourceController::class, 'store'])->name('resources.store');
    Route::post('resources/preview', [ResourceController::class, 'preview'])
        ->middleware('throttle:30,1')
        ->name('resources.preview');
    Route::patch('resources/{resource}', [ResourceController::class, 'update'])->name('resources.update');
    Route::post('resources/bulk', [ResourceController::class, 'bulk'])->name('resources.bulk');
    Route::patch('resources/{resource}/favorite', [ResourceController::class, 'favorite'])->name('resources.favorite');
    Route::patch('resources/{resource}/archive', [ResourceController::class, 'archive'])->name('resources.archive');
    Route::post('resources/{resource}/opened', [ResourceController::class, 'opened'])->name('resources.opened');
    Route::delete('resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');
    Route::get('collections', [CollectionController::class, 'index'])->name('collections.index');
    Route::post('collections', [CollectionController::class, 'store'])->name('collections.store');
    Route::patch('collections/{collection}', [CollectionController::class, 'update'])->name('collections.update');
    Route::delete('collections/{collection}', [CollectionController::class, 'destroy'])->name('collections.destroy');
    Route::patch('collections/{collection}/pin', [CollectionController::class, 'pin'])->name('collections.pin');
    Route::patch('collections/{collection}/collapse', [CollectionController::class, 'collapse'])->name('collections.collapse');
    Route::patch('collections/{collection}/move', [CollectionController::class, 'move'])->name('collections.move');
    Route::patch('collections/{collection}/resources/{resource}/move', [CollectionController::class, 'moveResource'])->name('collections.resources.move');
    Route::delete('collections/{collection}/resources/{resource}', [CollectionController::class, 'detachResource'])->name('collections.resources.destroy');
    Route::get('collections/{collection}', [CollectionController::class, 'show'])->name('collections.show');
    Route::get('snippets', [SnippetController::class, 'index'])->name('snippets.index');
    Route::post('snippets', [SnippetController::class, 'store'])->name('snippets.store');
    Route::patch('snippets/{snippet}/favorite', [SnippetController::class, 'favorite'])->name('snippets.favorite');
    Route::delete('snippets/{snippet}', [SnippetController::class, 'destroy'])->name('snippets.destroy');
});

require __DIR__.'/settings.php';
