#!/bin/bash
set -e

# Esperar a que MySQL esté disponible
echo "Esperando a MySQL..."
while ! nc -z mysql 3306; do
    sleep 1
done
echo "MySQL está disponible"

# Esperar a que Redis esté disponible
echo "Esperando a Redis..."
while ! nc -z redis 6379; do
    sleep 1
done
echo "Redis está disponible"

# Instalar dependencias de Composer si es necesario
if [ ! -d "/var/www/html/vendor" ]; then
    echo "Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Limpiar caché y calentar
php bin/console cache:clear --no-warmup
php bin/console cache:warmup

# Ejecutar migraciones (opcional, descomentar en producción)
# php bin/console doctrine:migrations:migrate --no-interaction

# Generar claves JWT si no existen
if [ ! -f "/var/www/html/config/jwt/private.pem" ]; then
    echo "Generando claves JWT..."
    mkdir -p /var/www/html/config/jwt
    php bin/console lexik:jwt:generate-keypair --skip-if-exists
fi

# Configurar permisos
chown -R appuser:appuser /var/www/html/var
chmod -R 775 /var/www/html/var

# Iniciar Supervisor para workers Messenger
echo "Iniciando Supervisor..."
service supervisor start

# Ejecutar el comando CMD
exec "$@"
