FROM php:8.3-fpm

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    zip \
    unzip \
    default-mysql-client \
    supervisor \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Crear usuario de aplicación
RUN useradd -G www-data,root -u 1000 appuser || exit 0
RUN mkdir -p /home/appuser/.composer && \
    chown -R appuser:appuser /home/appuser

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar archivos de composer primero para aprovechar caché de capas Docker
COPY composer.json composer.lock* ./

# Instalar dependencias de PHP durante el build (no en runtime)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts \
    && rm -rf /root/.composer/cache

# Copiar el resto de archivos de la aplicación
COPY . /var/www/html

# Configurar permisos
RUN chown -R appuser:appuser /var/www/html/var \
    && chmod -R 775 /var/www/html/var \
    && chown -R appuser:appuser /var/www/html/vendor \
    && chmod -R 775 /var/www/html/vendor

# Configurar Supervisor para el worker Messenger
COPY config/supervisor/messenger.conf /etc/supervisor/conf.d/messenger.conf

# Exponer puerto 9000 para PHP-FPM
EXPOSE 9000

# Script de entrada
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
