<?php

use App\Http\Controllers\Equipo6Controller;
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

// Equipo 6 — Evaluador de calificaciones por unidades
Route::post('/equipo-6/procesar', [Equipo6Controller::class, 'procesar']);
Route::match(['get', 'put', 'patch', 'delete'], '/equipo-6/procesar', [Equipo6Controller::class, 'metodoNoPermitido']);
