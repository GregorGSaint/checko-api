#!/bin/sh
set -e

cd /app

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --no-progress
fi

mkdir -p storage
php bin/console migrate
php bin/console token:seed-demo
chown -R www-data:www-data storage

exec "$@"
