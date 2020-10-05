FROM joomla:3.9-php7.3-apache as joomla_base

VOLUME /var/www/html

COPY default.conf /etc/apache2/sites-available/000-default.conf

RUN mkdir /etc/apache2/ssl/

COPY openssl.cnf /etc/apache2/ssl/openssl.localhost.cnf
RUN openssl req \
    -config /etc/apache2/ssl/openssl.localhost.cnf \
    -x509 \
    -nodes \
    -days 365 \
    -sha256 \
    -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/server.key \
    -out /etc/apache2/ssl/server.crt

# Enable SSL Module
RUN a2enmod ssl

# Install additional packages.
RUN apt-get update && apt-get install -y zip unzip git vim nano mariadb-client

# Install Composer.
RUN EXPECTED_SIGNATURE="$(curl https://composer.github.io/installer.sig)" \
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
  ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")" \
  if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ] \
  then \
    >&2 echo 'ERROR: Invalid installer signature' \
    rm composer-setup.php \
    exit 1 \
  fi \
  && php composer-setup.php --quiet --filename=composer \
  && mv composer /usr/local/bin \
  && rm composer-setup.php

# Install Joomlatools Console.
RUN mkdir /usr/src/composer \
  && cd /usr/src/composer \
  && composer require joomlatools/console "^1.5" \
  && ln -s /usr/src/composer/vendor/bin/joomla /usr/local/bin/joomla \
  && mkdir /usr/src/composer/vendor/joomlatools/console/bin/.files/cache

EXPOSE 80
EXPOSE 443
