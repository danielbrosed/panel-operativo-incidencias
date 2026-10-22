<?php

declare(strict_types=1);

use App\Http\Controllers\ActorController;
use App\Http\Controllers\IncidentCommentController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IncidentStatusController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/incidents');

Route::get('/incidents', [IncidentController::class, 'index'])->name('incidents.index');
Route::get('/incidents/{incident}', [IncidentController::class, 'show'])->whereNumber('incident')->name('incidents.show');

// Every write goes through the "writes" rate limiter (AppServiceProvider).
Route::middleware('throttle:writes')->group(function (): void {
    Route::post('/incidents', [IncidentController::class, 'store'])->name('incidents.store');
    Route::patch('/incidents/{incident}/status', IncidentStatusController::class)
        ->whereNumber('incident')
        ->name('incidents.status.update');
    Route::post('/incidents/{incident}/comments', [IncidentCommentController::class, 'store'])
        ->whereNumber('incident')
        ->name('incidents.comments.store');
    Route::put('/actor', [ActorController::class, 'update'])->name('actor.update');
});
