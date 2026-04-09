FROM php:8.2-apache

RUN apt-get update && apt-get install -y default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

RUN { \
      echo 'display_errors=Off'; \
      echo 'display_startup_errors=Off'; \
      echo 'error_reporting=E_ALL & ~E_DEPRECATED & ~E_STRICT'; \
      echo 'log_errors=On'; \
      echo 'default_charset=UTF-8'; \
      echo 'upload_max_filesize=12M'; \
      echo 'post_max_size=12M'; \
      echo 'max_file_uploads=20'; \
    } > /usr/local/etc/php/conf.d/zz-local.ini

COPY apache-000-default.conf /etc/apache2/sites-available/000-default.conf
COPY app/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html
