# javanile/mysql-import

[![StyleCI](https://github.styleci.io/repos/159405453/shield?branch=master)](https://github.styleci.io/repos/159405453)
[![Build Status](https://travis-ci.org/javanile/mysql-import.svg?branch=master)](https://travis-ci.org/javanile/mysql-import)
[![codecov](https://codecov.io/gh/javanile/mysql-import/branch/master/graph/badge.svg)](https://codecov.io/gh/javanile/mysql-import)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/e3a4ba8d9efe47129a2f74618334ba2e)](https://www.codacy.com/app/francescobianco/mysql-import?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=javanile/mysql-import&amp;utm_campaign=Badge_Grade)

The best way to import SQL file on your database.

## Get started

```bash
$ composer require javanile/mysql-import
```

```bash
$ ./vendor/bin/mysql-import database.sql
```

## Testing

```bash
$ docker-compose run --rm composer install
```

```bash
$ docker-compose run --rm phpunit tests
```
