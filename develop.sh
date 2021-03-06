#!/bin/bash
set -e

[[ -f mysql-import.log ]] && rm -f mysql-import.log

docker-compose down -v
docker-compose up -d mysql
#echo -n "[mysql-import] " && docker-compose run --rm -e MYSQL_HOST=ciao -e MYSQL_USER=root -e MYSQL_PASSWORD=secret php ./mysql-import ./tests/fixtures/database.sql
#echo -n "[mysql-import] " && docker-compose run --rm -e MYSQL_HOST=mysql -e MYSQL_USER=root -e MYSQL_PASSWORD=secret php ./mysql-import ./tests/fixtures/database.sql
echo -n "[mysql-import] " && docker-compose run --rm -e MYSQL_HOST=mysql -e MYSQL_USER=root -e MYSQL_PASSWORD=secret php ./mysql-import ./tests/fixtures/database.sql

if [[ -f mysql-import.log ]]; then
    echo "---[ mysql-import.log ]---"
    cat mysql-import.log
fi
