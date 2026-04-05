#!/bin/bash
set -e

echo "=== eWards PMS Starting ==="

# ── Set Apache to listen on Render's PORT (default 10000) ──
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/" /etc/apache2/sites-available/000-default.conf
echo "ServerName localhost" >> /etc/apache2/apache2.conf
echo "Apache will listen on port ${PORT}"

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

# ── Run pending migrations ──
php artisan migrate --force

# ── Fix permissions after cache commands (ran as root, Apache runs as www-data) ──
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo "=== Ready — starting Apache on port ${PORT} ==="
exec apache2-foreground
