FROM joomla:3.9-php7.1-apache as joomla_base

LABEL maintainer="Yoti SDK <sdksupport@yoti.com>"

VOLUME /var/www/html

COPY default.conf /etc/apache2/sites-available/000-default.conf
COPY ./keys/server.crt /etc/apache2/ssl/server.crt
COPY ./keys/server.key /etc/apache2/ssl/server.key

# Enable SSL Module
RUN a2enmod ssl

# Install additional packages.
RUN apt-get update && apt-get install -y zip unzip git vim nano

# Install MySQL Client.
RUN apt-get install -y mysql-client

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