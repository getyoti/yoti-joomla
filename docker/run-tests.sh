#!/bin/bash
docker-compose build joomla-base
docker-compose up -d joomla-dev

# Install Joomla plugin.
./install-joomla.sh joomla-dev

# Coding Standards
docker-compose exec joomla-dev sh -c "phpcs ./yoti-joomla"

# Run tests.
# docker-compose exec joomla-dev ./vendor/bin/phpunit
