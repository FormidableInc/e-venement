#!/usr/bin/env bash
set -ex

export ETCDCTL_API=3

# rand number to avoid build colision (same db used by two build)
if [ ! -f shuf.nbr ]
then
    shuf -i 200-600 -n 1 > shuf.nbr
fi

#RND may contain branch with '-' or upper case char which may not work as database name for postgre
suffix=$(echo $RND|sed -e s/-/_/g|tr '[:upper:]' '[:lower:]')$(echo -n $(cat shuf.nbr ))
prefix="/platform/build/$suffix"

if [ -z "$ETCDHOST" ]
then
    ETCDHOST="etcd.host"
fi
ETCDENDPOINT="--endpoints=http://${ETCDHOST}:2379"

if [ -z "$ETCDCTLCMD" ]
then
    #ETCDCTLCMD="docker exec $ETCDHOST etcdctl "
    ETCDCTLCMD="etcdctl"
fi

# check
$ETCDCTLCMD get  --prefix '/default' $ETCDENDPOINT

# get postgres default
postgreshost=$($ETCDCTLCMD get /default/postgres/hostname --print-value-only $ETCDENDPOINT)
postgresuser=$($ETCDCTLCMD get /default/postgres/root/username --print-value-only $ETCDENDPOINT)
postgrespass=$($ETCDCTLCMD get /default/postgres/root/password --print-value-only $ETCDENDPOINT)
# TODO add a check default cnx with psql

# set postgres env
$ETCDCTLCMD put $prefix/postgres/hostname $postgreshost $ETCDENDPOINT
$ETCDCTLCMD put $prefix/postgres/root/username $postgresuser $ETCDENDPOINT
$ETCDCTLCMD put $prefix/postgres/root/password $postgrespass $ETCDENDPOINT

$ETCDCTLCMD put $prefix/postgres/user/dbname eve_db_$suffix $ETCDENDPOINT
$ETCDCTLCMD put $prefix/postgres/user/username eve_user_$suffix $ETCDENDPOINT
$ETCDCTLCMD put $prefix/postgres/user/password eve_password_$suffix $ETCDENDPOINT

$ETCDCTLCMD get  --prefix $prefix $ETCDENDPOINT

confd -onetime -backend etcdv3 -node http://${ETCDHOST}:2379 -confdir ./etc/confd -log-level debug -prefix $prefix
