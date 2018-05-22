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


dbexists=$(psql -qt -w -h ${DBHOST} -U ${DBROOTUSER} -c "SELECT datname FROM pg_catalog.pg_database WHERE datname = '${DBAPPNAME}';"|sed -e s/' '//g)
if [ -z ${dbexists} ]
then
	echo "ERROR : Database ${DBAPPNAME} does not exist."
	exit 43
fi

dbempty=$(psql -qt -w -h ${DBHOST} -U ${DBROOTUSER} ${DBAPPNAME} -c "select count(*) from information_schema.tables where table_schema = 'public';"|sed -e s/' '//g)
if [ $dbempty -ne 0 ]
then
	echo "ERROR : Database ${DBAPPNAME} is NOT empty. Can not create structure."
	exit 44
fi

# We (Manu) do not like --all : ./symfony doctrine:build --all --application=default --no-confirmation
./symfony doctrine:build-model
./symfony doctrine:build-forms
./symfony doctrine:build-filters
./symfony doctrine:build-sql
# then insert data
./symfony doctrine:insert-sql
# todo: modify and commit the file
sed -i config/doctrine/functions-pgsql.sql -e s/'DROP AGGREGATE sum'/'DROP AGGREGATE IF EXISTS sum'/
cat  config/doctrine/functions-pgsql.sql
psql -w -U ${DBROOTUSER} -d ${DBAPPNAME} -h ${DBHOST} -f config/doctrine/functions-pgsql.sql
psql -w -U ${DBROOTUSER} -d ${DBAPPNAME} -h ${DBHOST} -c 'ALTER FUNCTION sum (boolean) owner to '$DBAPPUSER';'
psql -w -U ${DBROOTUSER} -d ${DBAPPNAME} -h ${DBHOST} -c 'ALTER FUNCTION sum_aggreg (integer, boolean) owner to '$DBAPPUSER';'
psql -w -U ${DBROOTUSER} -d ${DBAPPNAME} -h ${DBHOST} -c 'ALTER FUNCTION manifestation_ends_at ( timestamp without time zone, bigint) owner to '$DBAPPUSER';'

