#!/bin/bash
#
# ownCloud
#
# @author Thomas Müller
# @copyright 2012, 2013 Thomas Müller thomas.mueller@tmit.eu
#

#$EXECUTOR_NUMBER is set by Jenkins and allows us to run autotest in parallel
DATABASENAME=oc_autotest$EXECUTOR_NUMBER
DATABASEUSER=oc_autotest$EXECUTOR_NUMBER
ADMINLOGIN=admin$EXECUTOR_NUMBER
BASEDIR=$PWD

DBCONFIGS="sqlite mysql pgsql oci"
PHPUNIT=$(which phpunit)

function print_syntax {
	echo -e "Syntax: ./autotest.sh [dbconfigname] [testfile]\n" >&2
	echo -e "\t\"dbconfigname\" can be one of: $DBCONFIGS" >&2
	echo -e "\t\"testfile\" is the name of a test file, for example lib/template.php" >&2
	echo -e "\nExample: ./autotest.sh sqlite lib/template.php" >&2
	echo "will run the test suite from \"tests/lib/template.php\"" >&2
	echo -e "\nIf no arguments are specified, all tests will be run with all database configs" >&2
}

if ! [ -x $PHPUNIT ]; then
	echo "phpunit executable not found, please install phpunit version >= 3.7" >&2
	exit 3
fi

PHPUNIT_VERSION=$($PHPUNIT --version | cut -d" " -f2)
PHPUNIT_MAJOR_VERSION=$(echo $PHPUNIT_VERSION | cut -d"." -f1)
PHPUNIT_MINOR_VERSION=$(echo $PHPUNIT_VERSION | cut -d"." -f2)

if ! [ $PHPUNIT_MAJOR_VERSION -gt 3 -o \( $PHPUNIT_MAJOR_VERSION -eq 3 -a $PHPUNIT_MINOR_VERSION -ge 7 \) ]; then
	echo "phpunit version >= 3.7 required. Version found: $PHPUNIT_VERSION" >&2
	exit 4
fi

if ! [ -w config -a -w config/config.php ]; then
	echo "Please enable write permissions on config and config/config.php" >&2
	exit 1
fi

if [ $1 ]; then
	FOUND=0
	for DBCONFIG in $DBCONFIGS; do
		if [ $1 = $DBCONFIG ]; then
			FOUND=1
			break
		fi
	done
	if [ $FOUND = 0 ]; then
		echo -e "Unknown database config name \"$1\"\n" >&2
		print_syntax
		exit 2
	fi
fi

# Back up existing (dev) config if one exists
if [ -f config/config.php ]; then
	mv config/config.php config/config-autotest-backup.php
fi

function restore_config {
	# Restore existing config
	if [ -f config/config-autotest-backup.php ]; then
		mv config/config-autotest-backup.php config/config.php
	fi
}

# restore config on exit, even when killed
trap restore_config SIGINT SIGTERM

# use tmpfs for datadir - should speedup unit test execution
if [ -d /dev/shm ]; then
  DATADIR=/dev/shm/data-autotest$EXECUTOR_NUMBER
else
  DATADIR=$BASEDIR/data-autotest
fi

echo "Using database $DATABASENAME"

# create autoconfig for sqlite, mysql and postgresql
cat > ./tests/autoconfig-sqlite.php <<DELIM
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

cat > ./tests/autoconfig-mysql.php <<DELIM
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

cat > ./tests/autoconfig-pgsql.php <<DELIM
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

cat > ./tests/autoconfig-oci.php <<DELIM
<?php
\$AUTOCONFIG = array (
  'installed' => false,
  'dbtype' => 'oci',
  'dbtableprefix' => 'oc_',
  'adminlogin' => '$ADMINLOGIN',
  'adminpass' => 'admin',
  'directory' => '$DATADIR',
  'dbuser' => '$DATABASENAME',
  'dbname' => 'XE',
  'dbhost' => 'localhost',
  'dbpass' => 'owncloud',
);
DELIM

function execute_tests {
	echo "Setup environment for $1 testing ..."
	# back to root folder
	cd $BASEDIR

	# revert changes to tests/data
	git checkout tests/data

	# reset data directory
	rm -rf $DATADIR
	mkdir $DATADIR

	# remove the old config file
	#rm -rf config/config.php
	cp tests/preseed-config.php config/config.php

	# drop database
	if [ "$1" == "mysql" ] ; then
		mysql -u $DATABASEUSER -powncloud -e "DROP DATABASE $DATABASENAME"
	fi
	if [ "$1" == "pgsql" ] ; then
		dropdb -U $DATABASEUSER $DATABASENAME
	fi
	if [ "$1" == "oci" ] ; then
		echo "drop the database"
		sqlplus -s -l / as sysdba <<EOF
			drop user $DATABASENAME cascade;
EOF

		echo "create the database"
		sqlplus -s -l / as sysdba <<EOF
			create user $DATABASENAME identified by owncloud;
			alter user $DATABASENAME default tablespace users
			temporary tablespace temp
			quota unlimited on users;
			grant create session
			, create table
			, create procedure
			, create sequence
			, create trigger
			, create view
			, create synonym
			, alter session
			to $DATABASENAME;
			exit;
EOF
	fi

	# copy autoconfig
	cp $BASEDIR/tests/autoconfig-$1.php $BASEDIR/config/autoconfig.php

	# trigger installation
	echo "INDEX"
	php -f index.php | grep -i -C9999 error && echo "Error during setup" && exit 101
	echo "END INDEX"

	#test execution
	echo "Testing with $1 ..."
	cd tests
	rm -rf coverage-html-$1
	mkdir coverage-html-$1
	php -f enable_all.php | grep -i -C9999 error && echo "Error during setup" && exit 101
	if [ -z "$NOCOVERAGE" ]; then
		$PHPUNIT --configuration phpunit-autotest.xml --log-junit autotest-results-$1.xml --coverage-clover autotest-clover-$1.xml --coverage-html coverage-html-$1 $2 $3
		RESULT=$?
	else
		echo "No coverage"
		$PHPUNIT --configuration phpunit-autotest.xml --log-junit autotest-results-$1.xml $2 $3
		RESULT=$?
	fi
}

#
# start test execution
#
if [ -z "$1" ]
  then
	# run all known database configs
	for DBCONFIG in $DBCONFIGS; do
		execute_tests $DBCONFIG
	done
else
	execute_tests $1 $2 $3
fi

cd $BASEDIR

restore_config
#
# NOTES on mysql:
#  - CREATE DATABASE oc_autotest;
#  - CREATE USER 'oc_autotest'@'localhost' IDENTIFIED BY 'owncloud';
#  - grant all on oc_autotest.* to 'oc_autotest'@'localhost';
#
#  - for parallel executor support with EXECUTOR_NUMBER=0:
#  - CREATE DATABASE oc_autotest0;
#  - CREATE USER 'oc_autotest0'@'localhost' IDENTIFIED BY 'owncloud';
#  - grant all on oc_autotest0.* to 'oc_autotest0'@'localhost';
#
# NOTES on pgsql:
#  - su - postgres
#  - createuser -P oc_autotest (enter password and enable superuser)
#  - to enable dropdb I decided to add following line to pg_hba.conf (this is not the safest way but I don't care for the testing machine):
# local	all	all	trust
#
#  - for parallel executor support with EXECUTOR_NUMBER=0:
#  - createuser -P oc_autotest0 (enter password and enable superuser)
#
# NOTES on oci:
#  - it's a pure nightmare to install Oracle on a Linux-System
#  - DON'T TRY THIS AT HOME!
#  - if you really need it: we feel sorry for you
#
