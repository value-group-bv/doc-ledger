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

# Clear stale cache from the mounted volume and rebuild it
su-exec www-data php bin/console cache:clear --no-debug

exec "$@"
