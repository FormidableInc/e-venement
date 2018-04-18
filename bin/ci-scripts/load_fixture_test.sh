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
eveAdminEmail="heavy@eve.fr"
eveAdminUser="heavy"
eveAdminPassword="42"

./symfony guard:create-user ${eveAdminEmail} ${eveAdminUser} ${eveAdminPassword}
./symfony guard:promote ${eveAdminUser}
./symfony doctrine:data-load --append data/fixtures/10-permissions.yml
# Disable as not needed and takes a lot of time
# ./symfony doctrine:data-load --append data/fixtures/20-postalcodes.yml
# ./symfony doctrine:data-load --append data/fixtures/50-geo-fr-districts.yml
# ./symfony doctrine:data-load --append data/fixtures/50-geo-fr-dpt+regions.yml

./symfony doctrine:data-load --append data/fixtures/60-generic-data.yml
./symfony doctrine:data-load --append data/fixtures/61-type-of-relationships.yml
./symfony doctrine:data-load --append data/fixtures/66-country.yml
