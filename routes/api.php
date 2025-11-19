<?php


use GuzzleHttp\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Importación de controladores
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\BienController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\ResguardanteController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\OficinaController;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AreaFormController;
use App\Http\Controllers\DepartamentoFormController;
use App\Http\Controllers\EdificioController;
use App\Http\Controllers\OficinaFormController;
use App\Http\Controllers\GestorController;

use App\Http\Controllers\RolFormController;
use App\Http\Controllers\ResguardanteFormController;
use App\Http\Controllers\TraspasoController;

use App\Http\Controllers\QrGenerator;
use App\Http\Controllers\CatalogoCucopController;
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
        Route::get('/dashboard', [DashboardController::class, 'index']);
        
        /**
         * Ruta de recursos
        */
        Route::apiResource('areas', AreaController::class);
        Route::apiResource('bienes', BienController::class);
        Route::apiResource('resguardantes', ResguardanteController::class);
        Route::apiResource('departamentos', DepartamentoController::class);
        Route::apiResource('edificios', EdificioController::class);
        Route::apiResource('oficinas', OficinaController::class);
        Route::apiResource('gestores', GestorController::class);
        Route::apiResource('traspasos', TraspasoController::class);
        Route::apiResource('catalogo-camb-cucop', CatalogoCucopController::class)->parameters(['catalogo-camb-cucop' => 'catalogo']);

        Route::get('/dashboard', [DashboardController::class, 'index']);            //Vista general
        Route::get('/area-form-options', [AreaFormController::class, 'getOptions']); //Formulario de registro de áreas
        Route::get('formularios/departamentos', DepartamentoFormController::class)->name('formularios.departamentos'); //Formulario de registro de departamentos
        Route::get('formularios/oficinas', OficinaFormController::class)->name('formularios.oficinas'); //Formulario de registro de oficinas
        Route::get('formularios/roles', RolFormController::class)->name('formularios.roles'); // Formulario de roles
        Route::get('formularios/resguardantes', ResguardanteFormController::class)->name('formularios.resguardantes'); // Formulario de resguardantes
        Route::get('areas/{area}/structure', [AreaController::class, 'getStructure'])->name('areas.structure'); // Estructura jerarquica de área
        Route::get('oficinas/{oficina}/bienes', [OficinaController::class, 'getBienes'])->name('oficinas.bienes');
        Route::get('catalogo-cucop', [CatalogoCucopController::class, 'index'])->name('catalogo.index');

        Route::post('resguardantes/{resguardante}/crear-usuario', [ResguardanteController::class, 'crearUsuario'])->name('resguardantes.crearUsuario'); // Crear usuario para resguardante
        Route::put('bienes/{bien}/baja', [BienController::class, 'darDeBaja'])->name('bienes.darDeBaja');
        Route::put('bienes/{bien}/mover', [BienController::class, 'mover'])->name('bienes.mover');
        Route::post('inventario/comparar', [BienController::class, 'compararInventario']);
    });

    //Route::get('/obtenerqr/{cantidad}',[QrGenerator::class,'generarQrPdf']);
    
});
