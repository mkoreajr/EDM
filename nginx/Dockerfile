# syntax=docker/dockerfile:1
# Nginx: serves the static frontend and forwards app requests to PHP-FPM.
# Build context: the repository root.

FROM nginx:1.27-alpine

RUN rm -f /usr/share/nginx/html/*.html

COPY nginx/templates/ /etc/nginx/templates/
COPY frontend/ /usr/share/nginx/html/

# Used by the official image's envsubst step when it renders templates/*.template
ENV PORT=80 \
    PHP_FPM_HOST=backend:9000 \
    NGINX_ENVSUBST_FILTER="^(PORT|PHP_FPM_HOST)$"

EXPOSE 80

HEALTHCHECK --interval=15s --timeout=3s --start-period=20s --retries=3 \
  CMD wget -qO- http://127.0.0.1/health > /dev/null || exit 1
