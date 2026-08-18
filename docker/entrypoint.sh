#!/bin/sh
set -e

# Si no existe .env en el contenedor, copiar desde .env.example
if [ ! -f /var/www/html/.env ]; then
    echo "Copiando .env.example a .env..."
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Asegurar permisos de escritura
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Generar APP_KEY si falta
php artisan key:generate --force || true

# Limpiar y regenerar cache de configuración y rutas
php artisan config:clear || true
php artisan route:clear || true
php artisan config:cache || true
php artisan route:cache || true

echo "SIICOBS Moderno iniciado correctamente en el puerto 80"
exec /usr/bin/supervisord -c /etc/supervisord.conf
