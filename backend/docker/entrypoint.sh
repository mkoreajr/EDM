#!/bin/sh
# Runs database migrations, then starts the given command (php-fpm by default).
set -e

if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
  php /var/www/app/bin/migrate.php --wait=60
fi

exec "$@"
