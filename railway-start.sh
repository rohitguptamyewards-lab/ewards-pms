#!/bin/bash
set -e

echo "=== eWards PMS Startup ==="

# Ensure the persistent data directory exists (Railway volume mount point)
mkdir -p /app/storage/app
mkdir -p /app/storage/framework/cache
mkdir -p /app/storage/framework/sessions
mkdir -p /app/storage/framework/views
mkdir -p /app/storage/logs
mkdir -p /app/data

# Use /app/data for SQLite (this should be the Railway volume mount)
DB_PATH="/app/data/database.sqlite"

if [ ! -f "$DB_PATH" ]; then
    echo "Creating new SQLite database..."
    touch "$DB_PATH"
    echo "Running migrations..."
    php artisan migrate --force
    echo "Seeding database..."
    php artisan db:seed --force
else
    echo "Database exists, running pending migrations..."
    php artisan migrate --force
fi

# Cache config (after env vars are available at runtime)
php artisan config:cache
php artisan route:cache

echo "Starting server on port ${PORT:-8080}..."
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
