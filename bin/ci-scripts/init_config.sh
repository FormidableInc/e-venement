#!/usr/bin/env bash
set -x


for i in config/autoload.inc.php config/project.yml config/extra-plugins.php
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
