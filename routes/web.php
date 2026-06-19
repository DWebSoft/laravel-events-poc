<?php

use App\Http\Controllers\EventController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/events')->name('home');

Route::get('events', [EventController::class, 'index'])->name('events.index');
Route::get('events/data', [EventController::class, 'data'])->name('events.data');

Route::get('events-grid', [EventController::class, 'grid'])->name('events.grid');
Route::get('events-timeline', [EventController::class, 'timeline'])->name('events.timeline');

Route::get('events/{event}', [EventController::class, 'show'])->name('events.show')->whereUuid('event');

Route::inertia('dashboard', 'Dashboard')->name('dashboard');

require __DIR__.'/settings.php';
