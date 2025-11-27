# Stage 1: Build stage para compilar assets
FROM node:20-alpine AS build-assets

WORKDIR /app

# Copiar apenas arquivos necessários para build
COPY package*.json ./
RUN npm ci

COPY vite.config.ts tsconfig.json components.json ./
COPY resources ./resources
COPY public ./public

# Build dos assets
RUN npm run build

# Stage 2: PHP e Composer dependencies
FROM composer:latest AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --ignore-platform-reqs

# Stage 3: Imagem final de produção
FROM php:8.2-apache

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Habilitar mod_rewrite do Apache
RUN a2enmod rewrite

# Configurar Apache para Laravel
RUN sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|<Directory /var/www/html>|<Directory /var/www/html/public>|g' /etc/apache2/sites-available/000-default.conf \
    && echo '<Directory /var/www/html/public>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>' >> /etc/apache2/sites-available/000-default.conf

# Configurar o diretório de trabalho
WORKDIR /var/www/html

# Copiar dependências do Composer
COPY --from=composer-deps /app/vendor ./vendor

# Copiar arquivos do projeto (exceto node_modules e public/build que serão substituídos)
COPY . .

# Copiar assets compilados (sobrescreve qualquer build anterior)
COPY --from=build-assets /app/public/build ./public/build

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Script de inicialização
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expor a porta 80
EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

