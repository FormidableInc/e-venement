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

#todo: move this in .env or not
#eveAdminEmail="heavy@eve.fr"
#eveAdminUser="heavy"
#eveAdminPassword="42"

#./symfony guard:create-user ${eveAdminEmail} ${eveAdminUser} ${eveAdminPassword}
#./symfony guard:promote ${eveAdminUser}

optional_data=""

#use prefix number to set order
for i in data/fixtures/8*.yml
do
    optional_data="${optional_data} $(basename $i)"
done


for i in 10-permissions.yml 60-generic-data.yml 61-type-of-relationships.yml 66-country.yml $optional_data
do
    if [ -f data/fixtures/$i ]
       then
           ./symfony doctrine:data-load --append data/fixtures/$i
    fi
done


# Disable as not needed and takes a lot of time
# 20-postalcodes.yml 50-geo-fr-districts.yml 50-geo-fr-dpt+regions.yml
