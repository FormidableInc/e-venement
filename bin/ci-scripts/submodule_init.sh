#!/usr/bin/env bash
set -x

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
