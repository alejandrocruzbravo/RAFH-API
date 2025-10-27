<?php


use GuzzleHttp\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importación de controladores
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Rutas de API
|--------------------------------------------------------------------------
*/

// Middleware global para limpiar tokens expirados en todas las rutas API
Route::middleware([\App\Http\Middleware\CleanExpiredTokens::class])->group(function () {
    
    // Endpoints públicas para autenticación
    Route::post('/login', [AuthController::class, 'login']);

    // Rutas protegidas que requieren un token válido
    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);          // Cierre de sesión
        Route::post('/register',[RegisterController::class,'register']);    //Registro de gestor con usuario administrador
        

        Route::apiResource('bienes', BienController::class);
        Route::get('/dashboard', [DashboardController::class, 'index']);
    });
    
});
