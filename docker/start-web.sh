#!/usr/bin/env bash
set -Eeuo pipefail

cd /var/www/html

php artisan config:cache
php artisan route:cache
php artisan view:cache

php-fpm -F &
php_fpm_pid=$!

nginx -g 'daemon off;' &
nginx_pid=$!

shutdown() {
    kill -TERM "$nginx_pid" "$php_fpm_pid" 2>/dev/null || true
    wait "$nginx_pid" 2>/dev/null || true
    wait "$php_fpm_pid" 2>/dev/null || true
}

trap shutdown TERM INT
wait -n "$nginx_pid" "$php_fpm_pid"
shutdown
