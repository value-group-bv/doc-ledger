#!/bin/sh
set -e

cd /var/www/html

# Ensure runtime directories exist on the mounted volume
mkdir -p var/cache var/log var/sessions
chown -R www-data:www-data var/

# Run pending migrations
su-exec www-data php bin/console doctrine:migrations:migrate \
    --no-interaction \
    --allow-no-migration \
    --no-debug

# Warm up the Symfony cache
su-exec www-data php bin/console cache:warmup --no-debug

exec "$@"
