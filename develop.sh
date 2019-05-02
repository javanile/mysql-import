#!/bin/bash
set -e

[[ -f mysql-import.log ]] && rm mysql-import.log

docker-compose down -v
docker-compose up -d mysql
docker-compose run --rm -e MYSQL_HOST=ciao -e MYSQL_USER=root -e MYSQL_PASSWORD=secret php ./mysql-import ./tests/fixtures/database.sql

if [[ -f mysql-import.log ]]; then
    echo "---[ mysql-import.log ]---"
    cat mysql-import.log
fi
