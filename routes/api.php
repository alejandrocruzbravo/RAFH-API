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
use App\Http\Controllers\CatalogoCucopController;
use App\Http\Controllers\ResguardoController;

use App\Http\Controllers\MovimientoBienController;

use Illuminate\Support\Facades\Broadcast;
/*
|--------------------------------------------------------------------------
| Rutas de API
|--------------------------------------------------------------------------
*/

// Middleware global para limpiar tokens expirados en todas las rutas API
Route::middleware([\App\Http\Middleware\CleanExpiredTokens::class])->group(function () {
    // Endpoints públicas para autenticación
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);           // Cierre de sesión
        Route::get('/mis-bienes', [ResguardanteController::class, 'misBienes']);
        Route::get('areas/{area}/structure', [AreaController::class, 'getStructure'])->name('areas.structure'); // Estructura jerarquica de área
        Route::get('/areas', [AreaController::class, 'index']);
        Route::get('/resguardantes/search', [ResguardanteController::class, 'search']);
        Route::put('/bienes/{biene}', [BienController::class, 'update']);
        Route::get('/mis-movimientos', [ResguardanteController::class, 'misMovimientos']);
        Route::post('/traspasos', [TraspasoController::class, 'store']);
        Route::get('/mis-transferencias', [ResguardanteController::class, 'misTransferencias']);
        Route::get('/resguardante/dashboard', [ResguardanteController::class, 'dashboard']);
        
    });

    // Rutas protegidas que requieren un token válido
    Route::middleware(['auth:sanctum','role:Administrador'])->group(function () {
        /**
         * Ruta de recursos
        */
        Route::get('/dashboard', [DashboardController::class, 'index']);            //Vista general
        Route::get('/area-form-options', [AreaFormController::class, 'getOptions']); //Formulario de registro de áreas
        Route::get('formularios/departamentos', DepartamentoFormController::class)->name('formularios.departamentos'); //Formulario de registro de departamentos
        Route::get('formularios/oficinas', OficinaFormController::class)->name('formularios.oficinas'); //Formulario de registro de oficinas
        Route::get('formularios/roles', RolFormController::class)->name('formularios.roles'); // Formulario de roles
        Route::get('formularios/resguardantes', ResguardanteFormController::class)->name('formularios.resguardantes'); // Formulario de resguardantes
        
        Route::get('oficinas/{oficina}/bienes', [OficinaController::class, 'getBienes'])->name('oficinas.bienes'); // Bienes por oficina
        Route::get('catalogo-cucop', [CatalogoCucopController::class, 'index'])->name('catalogo.index'); // Listar catálogo CUCOP
        Route::get('/bienes/bajas', [BienController::class, 'bajas']);                 // Listar bienes dados de baja    
        Route::get('/bienes/buscar-codigo/{codigo}', [BienController::class, 'buscarPorCodigo']); // Buscar bien por código
        Route::get('/resguardantes/{id}/bienes', [ResguardanteController::class, 'bienesAsignados']); // Bienes asignados a resguardante
        Route::get('/configuracion-inventario', [ConfiguracionInventarioController::class, 'show']); // Obtener configuración de inventario
        Route::get('/oficinas/{id}/resguardantes', [ResguardanteController::class, 'indexByOficina']); //
        Route::get('/admin/movimientos', [MovimientoBienController::class, 'index']);

        Route::post('resguardantes/{resguardante}/crear-usuario', [ResguardanteController::class, 'crearUsuario'])->name('resguardantes.crearUsuario'); // Crear usuario para resguardante
        Route::post('inventario/comparar', [BienController::class, 'compararInventario']);
        Route::post('/inventario/levantamiento', [BienController::class, 'procesarLevantamiento']);
        Route::post('/bienes/{id}/foto', [BienController::class, 'subirFoto']);

        Route::apiResource('areas', AreaController::class)->except(['index']);
        Route::apiResource('bienes', BienController::class)->except(['update']);
        Route::apiResource('resguardantes', ResguardanteController::class);
        Route::apiResource('departamentos', DepartamentoController::class);
        Route::apiResource('edificios', EdificioController::class);
        Route::apiResource('oficinas', OficinaController::class);
        Route::apiResource('gestores', GestorController::class);
        Route::apiResource('traspasos', TraspasoController::class)->except(['store']);
        Route::apiResource('catalogo-camb-cucop', CatalogoCucopController::class)->parameters(['catalogo-camb-cucop' => 'catalogo']);
        Route::apiResource('resguardos', ResguardoController::class);  
    });

    Broadcast::routes(['middleware' => ['auth:sanctum']]);

});
