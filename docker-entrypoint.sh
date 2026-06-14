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

# Limpiar caché y calentar (solo si no existe)
if [ ! -f "/var/www/html/var/cache/prod/.warmup" ]; then
    php bin/console cache:warmup --no-optional-warmers
fi

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
