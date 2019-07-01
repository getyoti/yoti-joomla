#!/bin/bash
docker-compose build joomla-base
docker-compose up -d joomla-dev

# Coding Standards
docker-compose exec joomla-dev sh -c "phpcs ./yoti-joomla"
