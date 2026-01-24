<?php

use Dinithoshan\Racelab\Http\Controllers\AssetController;
use Dinithoshan\Racelab\Http\Controllers\DashboardController;
use Dinithoshan\Racelab\Http\Controllers\TimeLineController;
use Illuminate\Support\Facades\Route;

// Dashboard routes
Route::get('/racelab', [DashboardController::class, 'index'])->name('racelab');

// Asset routes
Route::get('/racelab-assets/{file}', [AssetController::class, 'serve'])->where('file', '.*\.(js|css)$')->name('racelab.assets');

// Api routes
Route::get('/api/racelabtimelineevents', [TimeLineController::class, 'index']);
Route::post('/api/racelabtimelineevents/flush', [TimeLineController::class, 'destroy']);