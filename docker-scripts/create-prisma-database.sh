#!/usr/bin/env bash

mysql --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS prisma_shadow;
    GRANT ALL PRIVILEGES ON \`prisma_shadow%\`.* TO '$MYSQL_USER'@'%';
EOSQL
