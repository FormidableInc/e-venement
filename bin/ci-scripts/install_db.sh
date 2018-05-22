#!/usr/bin/env bash
set -ex


# TODO share this between script (in an include)
if [ -f .env ]
then
    source .env
else
    echo "Please run this script from project root, and check .env file as it is mandatory"
    echo "If it is missing a quick solution is :"
    echo "ln -s .env.travis .env"
    exit 42
fi


./symfony doctrine:build --all --application=default --no-confirmation
# todo: modify and commit the file
sed -i config/doctrine/functions-pgsql.sql -e s/'DROP AGGREGATE sum'/'DROP AGGREGATE IF EXISTS sum'/
cat  config/doctrine/functions-pgsql.sql
psql -w -U ${DBAPPUSER} -d ${DBAPPNAME} -h ${DBHOST} -f config/doctrine/functions-pgsql.sql
