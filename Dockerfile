# ====================================================
#  ETAPA 1: Compilar Frontend Vue 3 + Quasar
# ====================================================
FROM node:20-alpine AS frontend-builder
WORKDIR /app/frontend

COPY frontend/package*.json ./
RUN npm install

COPY frontend/ ./
RUN npm run build

# ====================================================
#  ETAPA 2: Servidor de Producción PHP 8.3 + NGINX
# ====================================================
FROM php:8.3-fpm-alpine

# Instalar Nginx, Supervisor y herramientas base
RUN apk update && apk add --no-cache \
    nginx \
    supervisor \
    curl \
    git

# Instalar extensiones de PHP usando el instalador oficial recomendado
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql pdo_sqlite mbstring zip intl bcmath opcache

# Instalar Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar archivos del backend
COPY backend/ /var/www/html/

# Copiar el build compilado del frontend directamente a public/dist
COPY --from=frontend-builder /app/backend/public/dist /var/www/html/public/dist

# Instalar dependencias PHP de producción
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Permisos de almacenamiento y base de datos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Configuración de NGINX y Supervisord
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Script de arranque
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
