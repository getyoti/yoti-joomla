#!/bin/bash
./install-joomla.sh joomla-dev
docker-compose up -d selenium-node
sleep 15

# Coding Standards
docker-compose exec joomla-dev sh -c "phpcs ./yoti-joomla"

# Run Tests.
docker-compose exec joomla-dev ./vendor/bin/codecept run acceptance --steps
