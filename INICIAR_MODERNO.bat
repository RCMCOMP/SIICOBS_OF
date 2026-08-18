@echo off
title SIICOBS MODERNO - Sistema Integrado Banco de Sangre
echo ===================================================================
echo     INICIANDO SIICOBS MODERNO (Vue 3 + Quasar + Laravel 13 + ACL)
echo ===================================================================
echo.
echo 1. Iniciando Backend Laravel (PHP 8.5) en http://localhost:8000 ...
start "Backend Laravel API (8000)" cmd /k "cd /d "%~dp0backend" && "C:\PHP85\php.exe" artisan serve --port=8000"

echo 2. Iniciando Frontend Vue 3 + Quasar en http://localhost:5173 ...
start "Frontend Vue+Quasar (5173)" cmd /k "cd /d "%~dp0frontend" && npm run dev"

echo.
echo 3. Abriendo aplicacion en el navegador...
timeout /t 3 >nul
start "" http://localhost:5173/login

echo.
echo SIICOBS Moderno esta en ejecucion.
echo Usuario Admin: ADMI  /  Contrasena: QR183bnm
echo ===================================================================
pause
