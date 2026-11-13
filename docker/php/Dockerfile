FROM php:8.4-cli

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    zip \
    intl \
    opcache

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash \
    && mv /root/.symfony5/bin/symfony /usr/local/bin/symfony

# Configuración personalizada de PHP (hardening de seguridad)
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-security.ini

WORKDIR /var/www/html

# Exponer el puerto del servidor web de Symfony
EXPOSE 8000