<?php

use App\Http\Controllers\Equipo3Controller; // Import controller equipo 3
use App\Http\Controllers\OperationsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/generate-password', [
    OperationsController::class,
    'generate',
]);

// Ruta del equipo 3
Route::post('/equipo-3/validar-vuelo', [Equipo3Controller::class, 'procesarVuelo']);
