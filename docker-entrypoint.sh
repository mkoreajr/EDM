#!/bin/sh
set -e

PORT="${PORT:-10000}"

# Render expects the web server to listen on 0.0.0.0:$PORT.
sed -i -E "s/^Listen [0-9]+$/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i -E "s#<VirtualHost \*:[0-9]+>#<VirtualHost *:${PORT}>#" /etc/apache2/sites-available/000-default.conf

php /var/www/html/init_db.php
exec apache2-foreground
