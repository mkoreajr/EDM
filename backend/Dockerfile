# syntax=docker/dockerfile:1
#
# Build context: the repository root.
#
#   target "app"        -> PHP-FPM only (used by docker-compose with a separate Nginx container)
#   target "standalone" -> PHP-FPM + Nginx + static frontend in one image (default; used by Render)

############################################################
# base — PHP 8.3 FPM with the extensions the app needs
############################################################
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache libpq \
 && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS libpq-dev \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql opcache \
 && apk del .build-deps \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENV APP_ENV=production \
    APP_TIMEZONE=Africa/Dar_es_Salaam \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    PHP_FPM_MAX_CHILDREN=8

COPY backend/docker/php/app.ini     /usr/local/etc/php/conf.d/zz-app.ini
COPY backend/docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/zzz-app.conf
COPY backend/docker/entrypoint.sh  /usr/local/bin/app-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/app-entrypoint && chmod +x /usr/local/bin/app-entrypoint

############################################################
# app — application code (PHP-FPM listening on :9000)
############################################################
FROM base AS app

WORKDIR /var/www/app
COPY --chown=root:www-data backend/   /var/www/app/
COPY --chown=root:www-data database/  /var/www/database/
RUN rm -rf /var/www/app/docker /var/www/app/Dockerfile \
 && date +%s > /var/www/app/.build-id

HEALTHCHECK --interval=15s --timeout=3s --start-period=30s --retries=3 \
  CMD php -r "exit(@fsockopen('127.0.0.1', 9000) ? 0 : 1);"

ENTRYPOINT ["app-entrypoint"]
CMD ["php-fpm"]

############################################################
# standalone — app + Nginx + static frontend in one container
############################################################
FROM app AS standalone

RUN apk add --no-cache nginx gettext \
 && rm -f /etc/nginx/http.d/default.conf \
 && mkdir -p /run/nginx

COPY nginx/templates/default.conf.template /etc/nginx/templates/default.conf.template
COPY frontend/ /usr/share/nginx/html/
COPY backend/docker/start-standalone.sh /usr/local/bin/start-standalone
RUN sed -i 's/\r$//' /usr/local/bin/start-standalone && chmod +x /usr/local/bin/start-standalone

ENV PORT=10000 \
    PHP_FPM_HOST=127.0.0.1:9000

EXPOSE 10000

HEALTHCHECK --interval=15s --timeout=3s --start-period=30s --retries=3 \
  CMD wget -qO- "http://127.0.0.1:${PORT}/health" > /dev/null || exit 1

CMD ["start-standalone"]
