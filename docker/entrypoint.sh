#!/bin/sh
set -e

# Si no existe archivo .env en producción, crear uno a partir de variables de entorno
if [ ! -f /var/www/html/.env ]; then
    echo "Configurando entorno de producción..."
    touch /var/www/html/.env
fi

# Ajustar permisos
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Ejecutar optimizaciones de Laravel
php artisan config:cache || true
php artisan route:cache || true

echo "SIICOBS Moderno iniciado correctamente en el puerto 80"
exec /usr/bin/supervisord -c /etc/supervisord.conf
