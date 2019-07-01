#!/bin/bash
TARGET=$1

if [ "$TARGET" = "" ]; then
    TARGET="joomla"
fi

docker-compose down
docker-compose build joomla-base
docker-compose up -d $TARGET

# Wait for services to be ready
sleep 15

# Install Joomla
docker-compose exec -e JOOMLA_DB_HOST=joomladb $TARGET joomla site:install Yoti \
  -H joomladb \
  --mysql-database yotijoomla \
  --skip-create-statement \
  --www /var/www/html \
  --use-webroot-dir

# Enable Yoti module
if [ "$TARGET" != "joomla-base" ]; then
  docker-compose exec -T joomladb mysql -uroot -proot yotijoomla < ./mysql-dump.sql
fi
