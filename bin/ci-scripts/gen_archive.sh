#!/usr/bin/env bash
set -ex

Name=E-venement
Branch=$(git name-rev  --name-only $(git rev-parse HEAD) | sed -e s/\\^.*//g | awk -F'/' '{print $(NF)}')

# Clean current git dir
#git clean -df
#git checkout -- .

Filename=${Name}_${Branch}.tar.gz

rm -f ${Filename}

# warning ! can't use  --exclude=*.dist with tar as it does not take care of path (all file in the tree are not included like for example app/config/parameters.yml.dist
# gen archive --transform='s|\./|./'${Tag}'/|g'
tar --exclude-vcs \
    --exclude=build \
     -czf ${Filename} ./*

# -h

sha256sum ${Filename} > ${Filename}.sha256.txt
