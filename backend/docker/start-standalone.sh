#!/bin/sh
# Single-container mode (Render): PHP-FPM in the background, Nginx in the foreground.
# Both keep writing their logs to the container output.
set -e

envsubst '${PORT} ${PHP_FPM_HOST}' \
  < /etc/nginx/templates/default.conf.template \
  > /etc/nginx/http.d/default.conf

nginx -t -q
php-fpm --nodaemonize &
exec nginx -g 'daemon off;'
