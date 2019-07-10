#!/bin/bash
docker-compose build joomla-base
docker-compose up -d joomla-test
sleep 20

# Coding Standards.
docker-compose exec -T joomla-test phpcs

# Run Tests.
docker-compose exec -T joomla-test ./vendor/bin/codecept run acceptance --steps
