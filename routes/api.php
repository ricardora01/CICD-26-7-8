<?php

use App\Http\Controllers\Equipo4Controller;
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

Route::post('/equipo-4/evaluar-reinscripcion', [Equipo4Controller::class, 'evaluarReinscripcion'])
    ->middleware('throttle:60,1');
