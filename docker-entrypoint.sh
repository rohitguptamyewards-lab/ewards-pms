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

echo "=== Ready — starting Apache on port ${PORT} ==="
exec apache2-foreground
