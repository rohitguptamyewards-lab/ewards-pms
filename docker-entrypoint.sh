#!/bin/bash
set -e

echo "=== eWards PMS Starting ==="

# ── Set Apache to listen on Render's PORT on all interfaces ──
PORT="${PORT:-10000}"

# Overwrite ports.conf to bind explicitly to 0.0.0.0 (required for Render's port detection)
cat > /etc/apache2/ports.conf << PORTS
Listen 0.0.0.0:${PORT}
PORTS

# Update VirtualHost to match the port
sed -i "s/<VirtualHost \*:80>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf
echo "ServerName 0.0.0.0" >> /etc/apache2/apache2.conf
echo "Apache will listen on 0.0.0.0:${PORT}"

# ── PHP memory / performance tuning for free tier ──
cat > /usr/local/etc/php/conf.d/zz-render.ini << 'PHPINI'
memory_limit = 128M
opcache.enable = 1
opcache.memory_consumption = 64
opcache.max_accelerated_files = 4000
opcache.validate_timestamps = 0
error_log = /dev/stderr
log_errors = On
display_errors = Off
PHPINI

# ── Apache prefork tuning for limited memory ──
cat > /etc/apache2/mods-available/mpm_prefork.conf << 'MPM'
<IfModule mpm_prefork_module>
    StartServers             1
    MinSpareServers          1
    MaxSpareServers          3
    MaxRequestWorkers        5
    MaxConnectionsPerChild   500
</IfModule>
MPM

# ── Laravel optimizations ──
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Run pending migrations (don't abort startup on failure) ──
php artisan migrate --force || echo "WARNING: Migrations had issues, app will start anyway"

# ── Fix permissions after cache commands ──
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "=== Ready — starting Apache on 0.0.0.0:${PORT} ==="
exec apache2-foreground
