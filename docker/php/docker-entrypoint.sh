#!/bin/sh
set -e

# Reparar permisos automáticamente en cada inicio
echo "Reparando permisos de storage, logs y cache..."

# Aseguramos que la estructura interna exista por si acaso
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/logs

# Aplicamos permisos de forma recursiva a todo el árbol de storage y bootstrap/cache
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Limpiar el caché de configuración para que reconozca cambios en el .env y Redis
echo "Limpiando caché de Laravel..."
php artisan config:clear || true
php artisan cache:clear || true

# echo "Optimizando rutas..."
# php artisan route:cache || true

# En desarrollo, NO cacheamos configuración ni vistas para ver cambios al instante
# php artisan config:cache || true
# php artisan view:cache || true

# Luego el resto de tus comandos...
exec "$@"