FROM docker_joomla-base:latest

# Install Joomla Codesniffer.
RUN cd /usr/src/composer \
  && composer require squizlabs/php_codesniffer "~2.8" \
  && composer require joomla/coding-standards "~2.0@alpha" \
  && ln -s /usr/src/composer/vendor/bin/phpcs /usr/local/bin/phpcs \
  && ln -s /usr/src/composer/vendor/bin/phpcbf /usr/local/bin/phpcbf \
  && phpcs --config-set installed_paths /usr/src/composer/vendor/joomla/coding-standards

# Copy PHP Code Sniffer configuration.
COPY ./phpcs.xml.dist .
