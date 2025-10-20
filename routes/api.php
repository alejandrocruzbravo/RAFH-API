<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

/*
|--------------------------------------------------------------------------
| Rutas de API
|--------------------------------------------------------------------------
*/

// Rutas públicas para autenticación
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas que requieren un token válido
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);    // Cierre de sesión


});