#!/bin/bash
set -e

echo "=== eWards PMS Starting ==="

# ── Set Apache to listen on Render's PORT (default 10000) ──
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/" /etc/apache2/sites-available/000-default.conf
echo "Apache will listen on port ${PORT}"

# ── Laravel optimizations ──
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Run pending migrations ──
php artisan migrate --force

# ── Fix permissions after cache commands (ran as root, Apache runs as www-data) ──
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# ── Enable PHP error logging to stderr (visible in Render logs) ──
echo "error_log = /dev/stderr" >> /usr/local/etc/php/conf.d/docker-errors.ini
echo "log_errors = On" >> /usr/local/etc/php/conf.d/docker-errors.ini
echo "display_errors = Off" >> /usr/local/etc/php/conf.d/docker-errors.ini

echo "=== Ready — starting Apache on port ${PORT} ==="
exec apache2-foreground
