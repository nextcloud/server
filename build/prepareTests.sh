#!/bin/bash
#
# ownCloud
#
# @author Thomas Müller
# @author Morris Jobke
# @copyright 2012, 2013 Thomas Müller thomas.mueller@tmit.eu
# @copyright 2014 Morris Jobke hey@morrisjobke.de
#

DATABASENAME=oc_autotest
DATABASEUSER=oc_autotest
ADMINLOGIN=admin
BASEDIR=$PWD

# check for database parameter
if [ $1 ]; then
	DBCONFIGS="sqlite mysql pgsql oracle"
	FOUND=0
	for DBCONFIG in $DBCONFIGS; do
		if [ $1 = $DBCONFIG ]; then
			FOUND=1
			break
		fi
	done
	if [ $FOUND = 0 ]; then
		echo -e "Unknown database config name \"$1\"\n" >&2
		exit 2
	fi
else
	echo "Please pass in a database to use as first parameter" >&2
	exit 1
fi

# check if config dir and file is writable
if ! [[ -w config && ( !( -e config/config.php ) || -w config/config.php ) ]]; then
	echo "Please enable write permissions on config and config/config.php" >&2
	exit 1
fi

# use tmpfs for datadir - should speedup unit test execution
if [ -d /dev/shm ]; then
	DATADIR=/dev/shm/data-autotest
else
	DATADIR=$BASEDIR/data-autotest
fi

echo "Setup environment for $1 testing ..."
# revert changes to tests/data
git checkout tests/data/*

# reset data directory
rm -rf $DATADIR
mkdir $DATADIR

cp tests/preseed-config.php config/config.php

# # # # # #
# SQLite  #
# # # # # #
if [ "$1" == "sqlite" ] ; then
	cat > ./config/autoconfig.php <<DELIM
<?php
\$AUTOCONFIG = array (
	'installed' => false,
	'dbtype' => 'sqlite',
	'dbtableprefix' => 'oc_',
	'adminlogin' => '$ADMINLOGIN',
	'adminpass' => 'admin',
	'directory' => '$DATADIR',
);
DELIM
fi

# # # # #
# MySQL #
# # # # #
if [ "$1" == "mysql" ] ; then
	cat > ./config/autoconfig.php <<DELIM
<?php
\$AUTOCONFIG = array (
	'installed' => false,
	'dbtype' => 'mysql',
	'dbtableprefix' => 'oc_',
	'adminlogin' => '$ADMINLOGIN',
	'adminpass' => 'admin',
	'directory' => '$DATADIR',
	'dbuser' => '$DATABASEUSER',
	'dbname' => '$DATABASENAME',
	'dbhost' => 'localhost',
	'dbpass' => 'owncloud',
);
DELIM
fi

# # # # # # # #
# PostgreSQL  #
# # # # # # # #
if [ "$1" == "pgsql" ] ; then
	cat > ./config/autoconfig.php <<DELIM
<?php
\$AUTOCONFIG = array (
	'installed' => false,
	'dbtype' => 'pgsql',
	'dbtableprefix' => 'oc_',
	'adminlogin' => '$ADMINLOGIN',
	'adminpass' => 'admin',
	'directory' => '$DATADIR',
	'dbuser' => '$DATABASEUSER',
	'dbname' => '$DATABASENAME',
	'dbhost' => 'localhost',
	'dbpass' => 'owncloud',
);
DELIM

fi

# # # # # #
# Oracle  #
# # # # # #
if [ "$1" == "oracle" ] ; then
	build/prepareTestsOracle.sh $DATABASENAME $DATABASEUSER $ADMINLOGIN $DATADIR
fi

echo "Trigger ownCloud installation"
php -f index.php | grep -i -C9999 error && echo "Error during setup" && exit 101

echo "Enable apps ..."
cd tests
php -f enable_all.php | grep -i -C9999 error && echo "Error during setup" && exit 101
cd $BASEDIR

# show environment
echo "ownCloud configuration:"
cat $BASEDIR/config/config.php

echo "ownCloud data directory:"
ls -ll $DATADIR

echo "owncloud.log:"
cat $DATADIR/owncloud.log
