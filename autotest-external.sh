#!/usr/bin/env bash
#
# ownCloud
#
# @author Thomas Müller
# @author Morris Jobke
# @copyright 2012-2015 Thomas Müller thomas.mueller@tmit.eu
# @copyright 2014 Morris Jobke hey@morrisjobke.de
#

#$EXECUTOR_NUMBER is set by Jenkins and allows us to run autotest in parallel
DATABASENAME=oc_autotest$EXECUTOR_NUMBER
DATABASEUSER=oc_autotest$EXECUTOR_NUMBER
ADMINLOGIN=admin$EXECUTOR_NUMBER
BASEDIR=$PWD

DBCONFIGS="sqlite mysql pgsql oci"
PHPUNIT=$(which phpunit)

_XDEBUG_CONFIG=$XDEBUG_CONFIG
unset XDEBUG_CONFIG

function print_syntax {
	echo -e "Syntax: ./autotest-external.sh [dbconfigname] [startfile]\n" >&2
	echo -e "\t\"dbconfigname\" can be one of: $DBCONFIGS" >&2
	echo -e "\t\"startfile\" is the name of a start file inside the env/ folder in the files_external tests" >&2
	echo -e "\nExample: ./autotest.sh sqlite webdav-ownCloud" >&2
	echo "will run the external suite from \"apps/files_external/tests/env/start-webdav-ownCloud.sh\"" >&2
	echo -e "\nIf no arguments are specified, all available external backends will be run with all database configs" >&2
	echo -e "\nIf you specify 'common-tests' as startfile it will just run the tests that are independent from the backends" >&2
}

if ! [ -x "$PHPUNIT" ]; then
	echo "phpunit executable not found, please install phpunit version >= 3.7" >&2
	exit 3
fi

PHPUNIT_VERSION=$("$PHPUNIT" --version | cut -d" " -f2)
PHPUNIT_MAJOR_VERSION=$(echo $PHPUNIT_VERSION | cut -d"." -f1)
PHPUNIT_MINOR_VERSION=$(echo $PHPUNIT_VERSION | cut -d"." -f2)

if ! [ $PHPUNIT_MAJOR_VERSION -gt 4 -o \( $PHPUNIT_MAJOR_VERSION -eq 4 -a $PHPUNIT_MINOR_VERSION -ge 4 \) ]; then
	echo "phpunit version >= 4.4 required. Version found: $PHPUNIT_VERSION" >&2
	exit 4
fi

if ! [ \( -w config -a ! -f config/config.php \) -o \( -f config/config.php -a -w config/config.php \) ]; then
	echo "Please enable write permissions on config and config/config.php" >&2
	exit 1
fi

if [ "$1" ]; then
	FOUND=0
	for DBCONFIG in $DBCONFIGS; do
		if [ "$1" = $DBCONFIG ]; then
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

# Back up existing (dev) config if one exists and backup not already there
if [ -f config/config.php ] && [ ! -f config/config-autotest-backup.php ]; then
	mv config/config.php config/config-autotest-backup.php
fi

function cleanup_config {
	cd "$BASEDIR"
	# Restore existing config
	if [ -f config/config-autotest-backup.php ]; then
		mv config/config-autotest-backup.php config/config.php
	fi
	# Remove autotest config
	if [ -f config/autoconfig.php ]; then
		rm config/autoconfig.php
	fi
}

# restore config on exit
trap cleanup_config EXIT

# use tmpfs for datadir - should speedup unit test execution
if [ -d /dev/shm ]; then
  DATADIR=/dev/shm/data-autotest$EXECUTOR_NUMBER
else
  DATADIR=$BASEDIR/data-autotest
fi

echo "Using database $DATABASENAME"

function execute_tests {
	echo "Setup environment for $1 testing ..."
	# back to root folder
	cd "$BASEDIR"

	# revert changes to tests/data
	git checkout tests/data

	# reset data directory
	rm -rf "$DATADIR"
	mkdir "$DATADIR"

	# remove the old config file
	#rm -rf config/config.php
	cp tests/preseed-config.php config/config.php

	# drop database
	if [ "$1" == "mysql" ] ; then
		mysql -u $DATABASEUSER -powncloud -e "DROP DATABASE IF EXISTS $DATABASENAME" || true
	fi
	if [ "$1" == "pgsql" ] ; then
		dropdb -U $DATABASEUSER $DATABASENAME || true
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
		DATABASEUSER=$DATABASENAME
		DATABASENAME='XE'
	fi

	# copy autoconfig
	cp "$BASEDIR/tests/autoconfig-$1.php" "$BASEDIR/config/autoconfig.php"

	# trigger installation
	echo "Installing ...."
	./occ maintenance:install -vvv --database=$1 --database-name=$DATABASENAME --database-host=localhost --database-user=$DATABASEUSER --database-pass=owncloud --database-table-prefix=oc_ --admin-user=$ADMINLOGIN --admin-pass=admin --data-dir=$DATADIR

	#test execution
	echo "Testing with $1 ..."

	if [ -n "$2" ]; then
		echo "Run only $2 ..."
	fi

	cd tests
	rm -rf "coverage-external-html-$1"
	mkdir "coverage-external-html-$1"
	# just enable files_external
	php ../occ app:enable -vvv files_external
	if [[ "$_XDEBUG_CONFIG" ]]; then
		export XDEBUG_CONFIG=$_XDEBUG_CONFIG
	fi
	if [ -z "$NOCOVERAGE" ]; then
		"$PHPUNIT" --configuration phpunit-autotest-external.xml --log-junit "autotest-external-results-$1.xml" --coverage-clover "autotest-external-clover-$1.xml" --coverage-html "coverage-external-html-$1"
		RESULT=$?
	else
		echo "No coverage"
		"$PHPUNIT" --configuration phpunit-autotest-external.xml --log-junit "autotest-external-results-$1.xml"
		RESULT=$?
	fi

	if [ -n "$2" -a "$2" == "common-tests" ]; then
		return;
	fi

	FILES_EXTERNAL_BACKEND_PATH=../apps/files_external/tests/Storage
	FILES_EXTERNAL_BACKEND_ENV_PATH=../apps/files_external/tests/env

	for startFile in `ls -1 $FILES_EXTERNAL_BACKEND_ENV_PATH | grep start`; do
		name=`echo $startFile | sed 's/start-//' | sed 's/\.sh//'`

		if [ -n "$2" -a "$2" != "$name" ]; then
			echo "skip: $startFile"
			continue;
		fi

		echo "start: $startFile"
		echo "name: $name"

		# execute start file
		./$FILES_EXTERNAL_BACKEND_ENV_PATH/$startFile
		if [ $? -eq 0 ]; then
			# getting backend to test from filename
			# it's the part between the dots startSomething.TestToRun.sh
			testToRun=`echo $startFile | cut -d '-' -f 2`
			# capitalize first letter
			testToRun="${testToRun^}"
			testToRun="${testToRun}Test.php"

			# run the specific test
			if [ -z "$NOCOVERAGE" ]; then
				rm -rf "coverage-external-html-$1-$name"
				mkdir "coverage-external-html-$1-$name"
				"$PHPUNIT" --configuration phpunit-autotest-external.xml --log-junit "autotest-external-results-$1-$name.xml" --coverage-clover "autotest-external-clover-$1-$name.xml" --coverage-html "coverage-external-html-$1-$name" "$FILES_EXTERNAL_BACKEND_PATH/$testToRun"
				RESULT=$?
			else
				echo "No coverage"
				"$PHPUNIT" --configuration phpunit-autotest-external.xml --log-junit "autotest-external-results-$1-$name.xml" "$FILES_EXTERNAL_BACKEND_PATH/$testToRun"
				RESULT=$?
			fi
		else
		    DOEXIT=1
		fi

		# calculate stop file
		stopFile=`echo "$startFile" | sed 's/start/stop/'`
		echo "stop: $stopFile"
		if [ -f $FILES_EXTERNAL_BACKEND_ENV_PATH/$stopFile ]; then
			# execute stop file if existent
			./$FILES_EXTERNAL_BACKEND_ENV_PATH/$stopFile
		fi
		if [ "$DOEXIT" ]; then
		    echo "Error during start file execution ... terminating"
		    exit $DOEXIT
		fi
	done;
}

#
# start test execution
#
if [ -z "$1" ]; then
	# run all known database configs
	for DBCONFIG in $DBCONFIGS; do
		execute_tests $DBCONFIG "$2"
	done
else
	execute_tests "$1" "$2"
fi

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
