# ── Stage 1: Install Composer dependencies (needed for Ziggy JS) ────
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# ── Stage 2: Build frontend assets ──────────────────────────────────
FROM node:20-alpine AS frontend

WORKDIR /app
COPY package.json package-lock.json* ./
RUN npm ci

# Copy source code + vendor (Ziggy JS lives in vendor/tightenco/ziggy/dist/)
COPY . .
COPY --from=vendor /app/vendor vendor

RUN npm run build

# ── Stage 3: PHP runtime ───────────────────────────────────────────
FROM php:8.3-apache

# Install system dependencies + PostgreSQL client libs
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev \
        unzip curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql zip bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set Apache document root to Laravel's public folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
    /etc/apache2/sites-available/*.conf \
    /etc/apache2/apache2.conf

# Allow .htaccess overrides
RUN sed -ri -e 's/AllowOverride None/AllowOverride All/g' \
    /etc/apache2/apache2.conf

WORKDIR /var/www/html

# Copy application code
COPY . .

# Copy Composer vendor from Stage 1
COPY --from=vendor /app/vendor vendor

# Copy built frontend assets from Stage 2
COPY --from=frontend /app/public/build public/build

# Create storage directories and set permissions
RUN mkdir -p storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Render uses PORT env variable (default 10000)
EXPOSE 10000

# Copy startup script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

CMD ["/usr/local/bin/docker-entrypoint.sh"]
