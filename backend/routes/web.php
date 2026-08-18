<?php

use Illuminate\Support\Facades\Route;

Route::get('/api-info', function () {
    return response()->json([
        'status' => 'ONLINE',
        'sistema' => 'SIICOBS Moderno - Backend API (Laravel 13)',
        'version' => '1.0.0',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Redirigir cualquier ruta web al Frontend SPA compilado (Vue 3 + Quasar)
Route::fallback(function () {
    $spaPath = public_path('dist/index.html');
    if (file_exists($spaPath)) {
        return response(file_get_contents($spaPath), 200, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }
    
    return response()->json([
        'status' => 'ONLINE',
        'sistema' => 'SIICOBS Moderno - Backend API (Laravel 13)',
        'mensaje' => 'Frontend en desarrollo. Ejecuta npm run build en MODERNO/frontend para compilar la interfaz unificada.',
        'api_docs' => '/api/v1/dashboard/kpis'
    ]);
});
