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

# todo: get config from confd and/or private repo ?

for i in config/autoload.inc.php config/project.yml
do
    if [ -f ${i}.template ]
    then
        cp ${i}.template $i
    fi
done

for i in $(find apps -name "*.template")
do
    cp ${i} $(echo ${i} | sed -e s/.template$//)
done
