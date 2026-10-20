<?php

use App\Http\Controllers\Api\LocationApiController;
use App\Http\Controllers\Api\OpenLibrarySearchController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItineraryController;
use App\Http\Controllers\ItineraryStopController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\SavedLocationController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// Public catalog
Route::get('/', HomeController::class)->name('home');
Route::get('books', [BookController::class, 'index'])->name('books.index');
Route::get('books/{book}', [BookController::class, 'show'])->name('books.show');
Route::get('map', [LocationController::class, 'index'])->name('locations.index');
Route::get('locations/{location}', [LocationController::class, 'show'])->name('locations.show');
Route::get('search', SearchController::class)->middleware('throttle:search')->name('search');

// Public JSON endpoints (see docs/API.md)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('locations', [LocationApiController::class, 'index'])->middleware('throttle:search')->name('locations');
    Route::get('openlibrary/search', OpenLibrarySearchController::class)->middleware('throttle:openlibrary')->name('openlibrary.search');
});

// Authenticated
Route::middleware(['auth', 'verified', 'throttle:mutations'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('library', [LibraryController::class, 'index'])->name('library.index');
    Route::post('library', [LibraryController::class, 'store'])->name('library.store');
    Route::patch('library/{entry}', [LibraryController::class, 'update'])->name('library.update');
    Route::delete('library/{entry}', [LibraryController::class, 'destroy'])->name('library.destroy');

    Route::post('saved-locations', [SavedLocationController::class, 'store'])->name('saved-locations.store');
    Route::delete('saved-locations/{location}', [SavedLocationController::class, 'destroy'])->name('saved-locations.destroy');

    Route::resource('itineraries', ItineraryController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
    Route::post('itineraries/{itinerary}/stops', [ItineraryStopController::class, 'store'])->name('itineraries.stops.store');
    Route::put('itineraries/{itinerary}/stops/order', [ItineraryStopController::class, 'reorder'])->name('itineraries.stops.reorder');
    Route::patch('itineraries/{itinerary}/stops/{stop}', [ItineraryStopController::class, 'update'])->name('itineraries.stops.update');
    Route::delete('itineraries/{itinerary}/stops/{stop}', [ItineraryStopController::class, 'destroy'])->name('itineraries.stops.destroy');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
