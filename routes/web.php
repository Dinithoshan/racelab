<?php

use Dinithoshan\Racelab\Http\Controllers\DashboardController;
use Dinithoshan\Racelab\Http\Controllers\TimeLineController;
use Illuminate\Support\Facades\Route;

//dashboard routes
Route::get('/racelab', [DashboardController::class, 'index'])->name('racelab');


//api routes
Route::get('/api/racelabtimelineevents', [TimeLineController::class, 'index']);
Route::post('/api/racelabtimelineevents/flush', [TimeLineController::class, 'destroy']);