#!/usr/bin/env bash
set -x

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

if [ -z "${DBHOST}" ]
then
    echo "Please add DBHOST in .env file as it is mandatory"
    exit 42
fi

# Check if database exists before drop and re-create
psql -w -h ${DBHOST} -U ${DBROOTUSER} -lqt | grep -w ${DBAPPUSER} | grep -w ${DBAPPNAME}
if [ $? -ne 0 ]
   then
       psql -w -c "DROP DATABASE IF EXISTS ${DBAPPNAME};" -U ${DBROOTUSER} -h ${DBHOST}
       psql -w -c "DROP ROLE IF EXISTS ${DBAPPUSER};" -U ${DBROOTUSER} -h ${DBHOST}
       psql -w -c "CREATE USER ${DBAPPUSER} WITH PASSWORD '${DBAPPPASSWORD}';" -U ${DBROOTUSER} -h ${DBHOST}
       psql -w -c "ALTER ROLE ${DBAPPUSER} WITH CREATEDB;" -U ${DBROOTUSER} -h ${DBHOST}
       psql -w -c "CREATE DATABASE ${DBAPPNAME};" -U ${DBROOTUSER} -h ${DBHOST}
       psql -w -c "ALTER DATABASE ${DBAPPNAME} OWNER TO ${DBAPPUSER};" -U ${DBROOTUSER} -h ${DBHOST}
       psql -w -c 'CREATE EXTENSION "uuid-ossp";' -U ${DBROOTUSER} -d ${DBAPPNAME} -h ${DBHOST}

fi
