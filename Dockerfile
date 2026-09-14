FROM php:8.3-apache
RUN apt-get update && apt-get install -y libpq-dev && docker-php-ext-install pdo_pgsql && rm -rf /var/lib/apt/lists/*
COPY . /var/www/html/
RUN sed -i 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf && sed -i 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf
EXPOSE 10000
CMD ["apache2-foreground"]
