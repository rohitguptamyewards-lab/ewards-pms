#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ewards-pms}"
PHP_VERSION="${PHP_VERSION:-8.3}"

echo "[1/8] Installing system packages"
sudo apt-get update
sudo apt-get install -y software-properties-common curl gnupg ca-certificates lsb-release unzip git sqlite3 nginx

if ! command -v node >/dev/null 2>&1; then
    echo "[2/8] Installing Node.js 22"
    curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
    sudo apt-get install -y nodejs
else
    echo "[2/8] Node.js already installed"
fi

echo "[3/8] Installing PHP ${PHP_VERSION} and Composer"
sudo apt-get install -y \
    "php${PHP_VERSION}-fpm" \
    "php${PHP_VERSION}-cli" \
    "php${PHP_VERSION}-bcmath" \
    "php${PHP_VERSION}-curl" \
    "php${PHP_VERSION}-intl" \
    "php${PHP_VERSION}-mbstring" \
    "php${PHP_VERSION}-sqlite3" \
    "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-zip" \
    composer

if [ ! -d "${APP_DIR}/.git" ]; then
    echo "Repository not found at ${APP_DIR}"
    echo "Clone the repo there first, then rerun this script."
    exit 1
fi

cd "${APP_DIR}"

echo "[4/8] Preparing environment"
if [ ! -f .env ]; then
    cp .env.gcp-free.example .env
fi

if [ ! -f database/database.sqlite ]; then
    touch database/database.sqlite
fi

echo "[5/8] Installing application dependencies"
composer install --no-dev --optimize-autoloader
npm ci
npm run build

echo "[6/8] Running Laravel setup"
php artisan key:generate --force
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "[7/8] Fixing permissions"
sudo chown -R www-data:www-data storage bootstrap/cache database/database.sqlite
sudo find storage bootstrap/cache -type d -exec chmod 775 {} \;
sudo find storage bootstrap/cache -type f -exec chmod 664 {} \;
sudo chmod 664 database/database.sqlite

echo "[8/8] Enabling Nginx site"
sed \
    -e "s#__APP_ROOT__#${APP_DIR}#g" \
    -e "s#__PHP_VERSION__#${PHP_VERSION}#g" \
    deploy/gcp-free/nginx-ewards-pms.conf | sudo tee /etc/nginx/sites-available/ewards-pms >/dev/null
sudo ln -sfn /etc/nginx/sites-available/ewards-pms /etc/nginx/sites-enabled/ewards-pms
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart "php${PHP_VERSION}-fpm"
sudo systemctl reload nginx

cat <<EOF

Bootstrap complete.

Next:
1. Edit ${APP_DIR}/.env and set APP_URL to your VM IP or domain.
2. If you want demo data, run: php artisan db:seed --force
3. Open http://YOUR_VM_IP/health to verify the app is reachable.

Free-tier safe defaults already set:
- sqlite database on the VM disk
- local file uploads
- sync queue
- broadcast logging instead of Pusher
- log mailer

EOF
