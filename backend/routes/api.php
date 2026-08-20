<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DonanteController;
use App\Http\Controllers\Api\TriajeController;
use App\Http\Controllers\Api\FlebotomiaController;
use App\Http\Controllers\Api\FraccionamientoController;
use App\Http\Controllers\Api\SerologiaController;
use App\Http\Controllers\Api\InmunohematologiaController;
use App\Http\Controllers\Api\AlmacenController;
use App\Http\Controllers\Api\DespachoController;
use App\Http\Controllers\Api\FacturacionController;
use App\Http\Controllers\Api\ReportesController;
use App\Http\Controllers\Api\PortalClinicasController;
use App\Http\Controllers\Api\AdminAclController;
use App\Http\Middleware\CheckUserAcl;

Route::prefix('v1')->group(function () {
    // Rutas públicas
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Rutas autenticadas básicas
    Route::middleware([CheckUserAcl::class])->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
    });

    // Módulo Dashboard
    Route::middleware([CheckUserAcl::class.':dashboard'])->prefix('dashboard')->group(function () {
        Route::get('/stock', [DashboardController::class, 'stock']);
        Route::get('/kpis', [DashboardController::class, 'kpis']);
        Route::get('/actividades', [DashboardController::class, 'actividades']);
    });

    // Módulo Donantes
    Route::middleware([CheckUserAcl::class.':donantes'])->prefix('donantes')->group(function () {
        Route::get('/', [DonanteController::class, 'index']);
        Route::get('/{id}', [DonanteController::class, 'show']);
        Route::post('/', [DonanteController::class, 'store']);
        Route::put('/{id}', [DonanteController::class, 'update']);
    });

    // Módulo Triaje y Cuestionario
    Route::middleware([CheckUserAcl::class.':triaje'])->prefix('triaje')->group(function () {
        Route::get('/preguntas', [TriajeController::class, 'getQuestions']);
        Route::post('/evaluar', [TriajeController::class, 'evaluate']);
        Route::get('/rechazos', [TriajeController::class, 'getRejections']);
    });

    // Módulo Flebotomía
    Route::middleware([CheckUserAcl::class.':flebotomia'])->prefix('flebotomia')->group(function () {
        Route::get('/', [FlebotomiaController::class, 'index']);
        Route::get('/bolsas', [FlebotomiaController::class, 'getBolsas']);
        Route::get('/grupos', [FlebotomiaController::class, 'getGrupos']);
        Route::post('/', [FlebotomiaController::class, 'store']);
        Route::get('/etiqueta/{id}', [FlebotomiaController::class, 'getLabel']);
    });

    // Módulo Fraccionamiento
    Route::middleware([CheckUserAcl::class.':fraccionamiento'])->prefix('fraccionamiento')->group(function () {
        Route::get('/', [FraccionamientoController::class, 'index']);
        Route::post('/guardar', [FraccionamientoController::class, 'fractionate']);
    });

    // Módulo Serología
    Route::middleware([CheckUserAcl::class.':serologia'])->prefix('serologia')->group(function () {
        Route::get('/pruebas', [SerologiaController::class, 'getTests']);
        Route::post('/guardar', [SerologiaController::class, 'saveResults']);
    });

    // Módulo Inmunohematología & PCC
    Route::middleware([CheckUserAcl::class.':inmunohematologia'])->prefix('inmunohematologia')->group(function () {
        Route::get('/solicitudes', [InmunohematologiaController::class, 'getPendingRequests']);
        Route::post('/pcc', [InmunohematologiaController::class, 'savePcc']);
    });

    // Módulo Almacén
    Route::middleware([CheckUserAcl::class.':almacen'])->prefix('almacen')->group(function () {
        Route::get('/inventario', [AlmacenController::class, 'getInventory']);
        Route::post('/liberar', [AlmacenController::class, 'releaseUnit']);
    });

    // Módulo Despacho
    Route::middleware([CheckUserAcl::class.':despacho'])->prefix('despacho')->group(function () {
        Route::get('/centros', [DespachoController::class, 'getTransfusionCenters']);
        Route::get('/disponibles', [DespachoController::class, 'getAvailableUnits']);
        Route::post('/entregar', [DespachoController::class, 'deliverUnits']);
        Route::get('/nota-remision/{codigo}', [DespachoController::class, 'getNotaRemision']);
    });

    // Módulo Facturación
    Route::middleware([CheckUserAcl::class.':facturacion'])->prefix('facturacion')->group(function () {
        Route::get('/facturas', [FacturacionController::class, 'index']);
        Route::post('/crear', [FacturacionController::class, 'createInvoice']);
    });

    // Módulo Reportes
    Route::middleware([CheckUserAcl::class.':reportes'])->prefix('reportes')->group(function () {
        Route::get('/snis', [ReportesController::class, 'getSnisData']);
        Route::get('/trazabilidad/{code}', [ReportesController::class, 'getTraceability']);
    });

    // Módulo Portal Clínicas y Hospitales Externos
    Route::middleware([CheckUserAcl::class.':portal_clinicas'])->prefix('portal-clinicas')->group(function () {
        Route::get('/mis-solicitudes', [PortalClinicasController::class, 'getMyRequests']);
        Route::post('/solicitar', [PortalClinicasController::class, 'createRequest']);
    });

    // Módulo Administración y Matriz ACL
    Route::middleware([CheckUserAcl::class.':admin_acl'])->prefix('admin')->group(function () {
        Route::get('/usuarios', [AdminAclController::class, 'getUsers']);
        Route::post('/usuarios', [AdminAclController::class, 'createUser']);
        Route::put('/usuarios/{id}', [AdminAclController::class, 'updateUser']);
        Route::get('/recursos', [AdminAclController::class, 'getResources']);
        Route::get('/acl/{userId}', [AdminAclController::class, 'getUserAcl']);
        Route::post('/acl/{userId}', [AdminAclController::class, 'saveUserAcl']);
    });
});
