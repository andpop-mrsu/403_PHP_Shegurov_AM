<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\StepController;
use Illuminate\Support\Facades\Route;

Route::get('/games', [GameController::class, 'index']);
Route::get('/games/{id}', [GameController::class, 'show'])->whereNumber('id');
Route::post('/games', [GameController::class, 'store']);
Route::post('/games/{id}/steps', [StepController::class, 'store'])->whereNumber('id');
