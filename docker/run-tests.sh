#!/bin/bash
docker-compose build joomla-base
docker-compose up -d joomla-test
sleep 20

# Coding Standards.
docker-compose exec joomla-test sh -c "phpcs ./yoti-joomla"

# Run Tests.
docker-compose exec joomla-test ./vendor/bin/codecept run acceptance --steps
