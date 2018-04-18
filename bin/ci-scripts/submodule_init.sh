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

git submodule init
git submodule update

for i in plugins/sfDependencyInjectionPlugin lib/vendor/externals/symfony1
do
    if [ -d $i ]
    then
        cd $i
        git checkout master #todo: maybe add an env var for this
        cd -
    fi
done
