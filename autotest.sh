#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
# SPDX-FileCopyrightText: 2012-2016 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
#

DATABASENAME=oc_autotest
DATABASEUSER=oc_autotest
DATABASEHOST=localhost
ADMINLOGIN=admin
BASEDIR=$PWD

PRIMARY_STORAGE_CONFIGS="local swift s3 azure"
DBCONFIGS="sqlite mysql mariadb pgsql oci mysqlmb4"
EXTERNAL_STORAGE_CONFIGS="ftp sftp smb webdav amazons3"

# $PHP_EXE is run through 'which' and as such e.g. 'php' is usually
# sufficient. Due to the behaviour of 'which', $PHP_EXE may also be a path
# (absolute or not) to an executable, e.g. ./code/projects/php-src/sapi/cli/php.
if [ -z "$PHP_EXE" ]; then
	PHP_EXE=php
fi
PHP=$(which "$PHP_EXE")
if [ -z "$PHPUNIT_EXE" ]; then
	if [ -f lib/composer/bin/phpunit ]; then
		PHPUNIT_EXE="./lib/composer/bin/phpunit"
		PHPUNIT=$(readlink -f "$PHPUNIT_EXE")
	else
		PHPUNIT_EXE=phpunit
		PHPUNIT=$(which "$PHPUNIT_EXE")
	fi
fi

set -e

_XDEBUG_CONFIG=$XDEBUG_CONFIG
unset XDEBUG_CONFIG

# Get the IP address of a docker container
#
# @param $1 - The container id
function docker_get_ip {
	_docker_version=$(docker --version | grep -Po "(?<=Docker version )\d+")
	if [ "$_docker_version" -ge 29 ]; then
		docker inspect --format="{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}" "$1"
	else
		docker inspect --format="{{.NetworkSettings.IPAddress}}" "$1"
	fi
}

function print_syntax {
	echo -e "Syntax: ./autotest.sh [dbconfigname] [testfile]\n" >&2
	echo -e "\t\"dbconfigname\" can be one of: $DBCONFIGS" >&2
	echo -e "\t\"testfile\" is the name of a test file, for example lib/template.php" >&2
	echo -e "\nExample: ./autotest.sh sqlite lib/template.php" >&2
	echo "will run the test suite from \"tests/lib/template.php\"" >&2
	echo -e "\nIf no arguments are specified, all tests will be run with all database configs" >&2
	echo -e "\nEnvironment variables:" >&2
	echo -e "\tUSEDOCKER=1\t\t\tSpin up a Docker container for the database (mysql/mariadb/pgsql)," >&2
	echo -e "\t\t\t\t\tMemcached (with ENABLE_MEMCACHE=memcached), or primary object store" >&2
	echo -e "\t\t\t\t\t(with PRIMARY_STORAGE_CONFIG=s3 or azure)" >&2
	echo -e "\tPRIMARY_STORAGE_CONFIG=s3\tUse MinIO as primary object store (USEDOCKER=1 recommended)" >&2
	echo -e "\tPRIMARY_STORAGE_CONFIG=azure\tUse Azurite as primary object store (USEDOCKER=1 recommended)" >&2
	echo -e "\tENABLE_MEMCACHE=memcached\tEnable Memcached as cache backend (USEDOCKER=1 recommended)" >&2
	echo -e "\tEXTERNAL_STORAGE=<backend>\tRun files_external storage tests using Docker." >&2
	echo -e "\t\t\t\t\tBackend can be one of: $EXTERNAL_STORAGE_CONFIGS" >&2
	echo -e "\t\t\t\t\tExample: EXTERNAL_STORAGE=ftp ./autotest.sh" >&2
}

if [ -x "$PHP" ]; then
	echo "Using PHP executable $PHP"
else
	echo "Could not find PHP executable $PHP_EXE" >&2
	exit 3
fi

if ! [ -x "$PHPUNIT" ]; then
	echo "phpunit executable not found, please install phpunit version >= 11.5 manually or via:" >&2
	echo "  composer install" >&2
	exit 3
fi

# PHPUnit might also be installed via a facade binary script
if [[ "$PHPUNIT" =~ \.phar$ ]]; then
  PHPUNIT=( "$PHP" "$PHPUNIT" )
else
  PHPUNIT=( "$PHPUNIT" )
fi

PHPUNIT_VERSION=$($PHPUNIT --version | cut -d" " -f2)
PHPUNIT_MAJOR_VERSION=$(echo "$PHPUNIT_VERSION" | cut -d"." -f1)
PHPUNIT_MINOR_VERSION=$(echo "$PHPUNIT_VERSION" | cut -d"." -f2)

if ! [ "$PHPUNIT_MAJOR_VERSION" -gt 11 -o \( "$PHPUNIT_MAJOR_VERSION" -eq 11 -a "$PHPUNIT_MINOR_VERSION" -ge 5 \) ]; then
	echo "phpunit version >= 11.5 required. Version found: $PHPUNIT_VERSION" >&2
	exit 4
fi

if ! [ \( -w config -a ! -f config/config.php \) -o \( -f config/config.php -a -w config/config.php \) ]; then
	echo "Please enable write permissions on config and config/config.php" >&2
	exit 1
fi

if [ "$1" ]; then
	FOUND=0
	for DBCONFIG in $DBCONFIGS; do
		if [ "$1" = "$DBCONFIG" ]; then
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
if [ "$PRIMARY_STORAGE_CONFIG" ]; then
	FOUND=0
	for PSC in $PRIMARY_STORAGE_CONFIGS; do
		if [ "$PRIMARY_STORAGE_CONFIG" = "$PSC" ]; then
			FOUND=1
			break
		fi
	done
	if [ $FOUND = 0 ]; then
		echo -e "Unknown primary storage config name \"$PRIMARY_STORAGE_CONFIG\"\n" >&2
		print_syntax
		exit 2
	fi
else
	PRIMARY_STORAGE_CONFIG="local"
fi
if [ "$EXTERNAL_STORAGE" ]; then
	FOUND=0
	for ES in $EXTERNAL_STORAGE_CONFIGS; do
		if [ "$EXTERNAL_STORAGE" = "$ES" ]; then
			FOUND=1
			break
		fi
	done
	if [ $FOUND = 0 ]; then
		echo -e "Unknown external storage backend \"$EXTERNAL_STORAGE\"\n" >&2
		print_syntax
		exit 2
	fi
fi

# Back up existing (dev) config if one exists and backup not already there
if [ -f config/config.php ] && [ ! -f config/config-autotest-backup.php ]; then
	mv config/config.php config/config-autotest-backup.php
fi

function cleanup_config {

	if [ ! -z "$DOCKER_CONTAINER_ID" ]; then
		echo "Kill the docker $DOCKER_CONTAINER_ID"
		docker stop "$DOCKER_CONTAINER_ID"
		docker rm -f "$DOCKER_CONTAINER_ID"
	fi

	if [ ! -z "$DOCKER_SERVICE_CONTAINER_ID" ]; then
		echo "Kill the service docker $DOCKER_SERVICE_CONTAINER_ID"
		docker stop "$DOCKER_SERVICE_CONTAINER_ID"
		docker rm -f "$DOCKER_SERVICE_CONTAINER_ID"
	fi

	cd "$BASEDIR"
	if [ "$PRIMARY_STORAGE_CONFIG" == "swift" ] ; then
		echo "Kill the swift docker"
		tests/objectstore/stop-swift-ceph.sh
	fi
	# Restore existing config
	if [ -f config/config-autotest-backup.php ]; then
		mv config/config-autotest-backup.php config/config.php
	fi
	# Remove autotest config
	if [ -f config/autoconfig.php ]; then
		rm config/autoconfig.php
	fi
	# Remove autotest swift storage config
	if [ -f config/autotest-storage-swift.config.php ]; then
		rm config/autotest-storage-swift.config.php
	fi
	# Remove autotest redis config
	if [ -f config/redis.config.php ]; then
		rm config/redis.config.php
	fi
	# Remove autotest memcached config
	rm -f config/memcached.config.php
	# Remove mysqlmb4.config.php
	rm -f config/mysqlmb4.config.php
	# Remove external storage test config files written by execute_external_tests
	rm -f apps/files_external/tests/config.ftp.php
	rm -f apps/files_external/tests/config.sftp.php
	rm -f apps/files_external/tests/config.smb.php
	rm -f apps/files_external/tests/config.webdav.php
	rm -f apps/files_external/tests/config.amazons3.php

	# restore .htaccess
	git restore .htaccess
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

function execute_external_tests {
	STORAGE=$1
	echo "Setting up environment for files_external storage testing: $STORAGE ..."
	cd "$BASEDIR"

	# Pre-flight checks for storage-specific system dependencies
	if [ "$STORAGE" == "smb" ] ; then
		if ! "$PHP" -m | grep -qi '^smbclient$' && ! which smbclient > /dev/null 2>&1; then
			echo "[ERROR] Neither the PHP 'smbclient' extension nor the 'smbclient' binary is installed." >&2
			echo "        Install one of:" >&2
			echo "          sudo apt install php-smbclient" >&2
			echo "          sudo apt install smbclient" >&2
			exit 1
		fi
		if ! which smbclient > /dev/null 2>&1; then
			echo "[WARNING] The 'smbclient' binary is not installed — notify tests will be skipped." >&2
			echo "          Install with: sudo apt install smbclient" >&2
		fi
	fi

	git checkout tests/data
	rm -rf "$DATADIR"
	mkdir "$DATADIR"
	cp tests/preseed-config.php config/config.php

	echo "Installing Nextcloud with SQLite for external storage tests ..."
	"$PHP" ./occ maintenance:install -vvv \
		--database=sqlite \
		--database-name=nextcloud \
		--admin-user="$ADMINLOGIN" \
		--admin-pass=admin \
		--data-dir="$DATADIR"

	"$PHP" ./occ app:enable --force files_external
	"$PHP" ./occ config:system:set --value true --type boolean allow_local_remote_servers

	TEST_FILE=""

	if [ "$STORAGE" == "ftp" ] ; then
		echo "Fire up the FTP docker (vsftpd)"
		mkdir -p /tmp/ftp
		DOCKER_SERVICE_CONTAINER_ID=$(docker run -d --net host \
			-e FTP_USER=test \
			-e FTP_PASS=test \
			-e PASV_ADDRESS=127.0.0.1 \
			-v /tmp/ftp:/home/vsftpd/test \
			fauria/vsftpd)
		echo "Waiting for FTP initialisation ..."
		if ! apps/files_external/tests/env/wait-for-connection localhost 21 30; then
			echo "[ERROR] Waited 30 seconds, no FTP response" >&2
			exit 1
		fi
		echo "FTP is up."
		echo "<?php return ['run' => true, 'host' => 'localhost', 'user' => 'test', 'password' => 'test', 'root' => ''];" \
			> apps/files_external/tests/config.ftp.php
		TEST_FILE="apps/files_external/tests/Storage/FtpTest.php"
	fi

	if [ "$STORAGE" == "sftp" ] ; then
		echo "Fire up the sFTP docker (atmoz/sftp)"
		mkdir -p /tmp/sftp
		chmod 777 /tmp/sftp
		DOCKER_SERVICE_CONTAINER_ID=$(docker run -d \
			-e SFTP_USERS="test:test:::upload" \
			-p 2222:22 \
			-v /tmp/sftp:/home/test/upload \
			atmoz/sftp)
		echo "Waiting for sFTP initialisation ..."
		if ! apps/files_external/tests/env/wait-for-connection localhost 2222 30; then
			echo "[ERROR] Waited 30 seconds, no sFTP response" >&2
			exit 1
		fi
		# Give the SSH daemon a moment to finish initialising after the port is open
		sleep 2
		echo "sFTP is up."
		echo "<?php return ['run' => true, 'host' => 'localhost:2222', 'user' => 'test', 'password' => 'test', 'root' => 'upload'];" \
			> apps/files_external/tests/config.sftp.php
		TEST_FILE="apps/files_external/tests/Storage/SftpTest.php"
	fi

	if [ "$STORAGE" == "smb" ] ; then
		echo "Fire up the SMB docker (Samba)"
		DOCKER_SERVICE_CONTAINER_ID=$(docker run -d \
			-e ACCOUNT_test=test \
			-e UID_test=1000 \
			-e 'SAMBA_VOLUME_CONFIG_test=[public]; path=/tmp; valid users = test; guest ok = no; read only = no; browseable = yes' \
			-p 445:445 \
			ghcr.io/servercontainers/samba:smbd-only-a3.18.0-s4.18.2-r0)
		echo "Waiting for SMB initialisation ..."
		if ! apps/files_external/tests/env/wait-for-connection localhost 445 60; then
			echo "[ERROR] Waited 60 seconds, no SMB response" >&2
			exit 1
		fi
		echo "SMB is up."
		sleep 5
		echo "<?php return ['run' => true, 'host' => 'localhost', 'user' => 'test', 'password' => 'test', 'root' => '', 'share' => 'public'];" \
			> apps/files_external/tests/config.smb.php
		TEST_FILE="apps/files_external/tests/Storage/SmbTest.php"
	fi

	if [ "$STORAGE" == "webdav" ] ; then
		echo "Fire up the WebDAV docker (Apache)"
		DOCKER_SERVICE_CONTAINER_ID=$(docker run -d \
			-p 8081:80 \
			ghcr.io/nextcloud/continuous-integration-webdav-apache:latest)
		echo "Waiting for WebDAV initialisation ..."
		if ! apps/files_external/tests/env/wait-for-connection localhost 8081 30; then
			echo "[ERROR] Waited 30 seconds, no WebDAV response" >&2
			exit 1
		fi
		echo "WebDAV is up."
		echo "<?php return ['run' => true, 'host' => 'localhost:8081/webdav/', 'user' => 'test', 'password' => 'pass', 'root' => '', 'wait' => 0];" \
			> apps/files_external/tests/config.webdav.php
		TEST_FILE="apps/files_external/tests/Storage/WebdavTest.php"
	fi

	if [ "$STORAGE" == "amazons3" ] ; then
		echo "Fire up the LocalStack docker (S3 emulator)"
		DOCKER_SERVICE_CONTAINER_ID=$(docker run -d \
			-e SERVICES=s3 \
			-e DEBUG=1 \
			-p 4566:4566 \
			"localstack/localstack@sha256:9d4253786e0effe974d77fe3c390358391a56090a4fff83b4600d8a64404d95d")
		echo "Waiting for LocalStack initialisation ..."
		if ! apps/files_external/tests/env/wait-for-connection localhost 4566 60; then
			echo "[ERROR] Waited 60 seconds, no LocalStack response" >&2
			exit 1
		fi
		# wait-for-connection only checks TCP; poll the health endpoint until S3 is running
		for i in $(seq 1 30); do
			if curl -sf http://localhost:4566/_localstack/health 2>/dev/null | grep -qE '"s3": "(running|available)"'; then
				break
			fi
			sleep 1
			if [ "$i" -eq 30 ]; then
				echo "[ERROR] LocalStack S3 service did not become ready in time" >&2
				exit 1
			fi
		done
		echo "LocalStack is up."
		echo "<?php return ['run' => true, 'localstack' => true, 'key' => 'ignored', 'secret' => 'ignored', 'bucket' => 'bucket', 'hostname' => 'localhost', 'port' => 4566, 'use_ssl' => false, 'autocreate' => true, 'use_path_style' => true];" \
			> apps/files_external/tests/config.amazons3.php
		TEST_FILE="apps/files_external/tests/Storage/Amazons3Test.php apps/files_external/tests/Storage/VersionedAmazonS3Test.php apps/files_external/tests/Storage/Amazons3MultiPartTest.php"
	fi

	echo "Testing files_external/$STORAGE ..."
	cd "$BASEDIR"
	set +e
	"${PHPUNIT[@]}" --fail-on-warning --fail-on-risky --display-warnings --display-deprecations --display-phpunit-deprecations --colors=always --bootstrap tests/bootstrap.php $TEST_FILE
	RESULT=$?
	set -e

	if [ ! -z "$DOCKER_SERVICE_CONTAINER_ID" ] ; then
		echo "Kill the service docker $DOCKER_SERVICE_CONTAINER_ID"
		docker stop $DOCKER_SERVICE_CONTAINER_ID
		docker rm -f $DOCKER_SERVICE_CONTAINER_ID
		unset DOCKER_SERVICE_CONTAINER_ID
	fi

	exit $RESULT
}

function execute_tests {
	DB=$1
	echo "Setup environment for $DB testing on $PRIMARY_STORAGE_CONFIG storage ..."
	# back to root folder
	cd "$BASEDIR"

	# revert changes to tests/data
	git checkout tests/data

	# reset data directory
	rm -rf "$DATADIR"
	mkdir "$DATADIR"

	if [ "$PRIMARY_STORAGE_CONFIG" == "swift" ] ; then
		tests/objectstore/start-swift-ceph.sh
		cp tests/objectstore/swift.config.php config/autotest-storage-swift.config.php
	fi
	if [ "$PRIMARY_STORAGE_CONFIG" == "s3" ] ; then
		if [ ! -z "$USEDOCKER" ] ; then
			echo "Fire up the MinIO docker"
			DOCKER_SERVICE_CONTAINER_ID=$(docker run -d \
				-e MINIO_ROOT_USER=nextcloud \
				-e MINIO_ROOT_PASSWORD=bWluaW8tc2VjcmV0LWtleS1uZXh0Y2xvdWQ= \
				-p 9000:9000 \
				minio/minio server /data)
			echo "Waiting for MinIO initialisation ..."
			if ! apps/files_external/tests/env/wait-for-connection localhost 9000 60; then
				echo "[ERROR] Waited 60 seconds, no MinIO response" >&2
				exit 1
			fi
			echo "MinIO is up."
		fi
		export OBJECT_STORE=s3
		export OBJECT_STORE_HOST="${OBJECT_STORE_HOST:-localhost}"
		export OBJECT_STORE_KEY="${OBJECT_STORE_KEY:-nextcloud}"
		export OBJECT_STORE_SECRET="${OBJECT_STORE_SECRET:-bWluaW8tc2VjcmV0LWtleS1uZXh0Y2xvdWQ=}"
	fi
	if [ "$PRIMARY_STORAGE_CONFIG" == "azure" ] ; then
		if [ ! -z "$USEDOCKER" ] ; then
			echo "Fire up the Azurite docker"
			DOCKER_SERVICE_CONTAINER_ID=$(docker run -d \
				-e AZURITE_ACCOUNTS=nextcloud:bmV4dGNsb3Vk \
				-p 10000:10000 \
				mcr.microsoft.com/azure-storage/azurite)
			echo "Waiting for Azurite initialisation ..."
			if ! apps/files_external/tests/env/wait-for-connection localhost 10000 60; then
				echo "[ERROR] Waited 60 seconds, no Azurite response" >&2
				exit 1
			fi
			echo "Azurite is up."
		fi
		export OBJECT_STORE=azure
		export OBJECT_STORE_HOST="${OBJECT_STORE_HOST:-localhost}"
		export OBJECT_STORE_KEY="${OBJECT_STORE_KEY:-nextcloud}"
		export OBJECT_STORE_SECRET="${OBJECT_STORE_SECRET:-bmV4dGNsb3Vk}"
	fi
	cp tests/preseed-config.php config/config.php

	if [ "$ENABLE_REDIS" == "true" ] ; then
		cp tests/redis.config.php config/redis.config.php
	elif [ "$ENABLE_REDIS_CLUSTER" == "true" ] ; then
		cp tests/redis-cluster.config.php config/redis.config.php
	fi
	if [ "$ENABLE_MEMCACHE" == "memcached" ] ; then
		if ! "$PHP" -m | grep -qi '^memcached$'; then
			echo "[ERROR] The PHP 'memcached' extension is not installed." >&2
			echo "        Install it with: sudo apt install php-memcached" >&2
			echo "        or: sudo pecl install memcached" >&2
			exit 1
		fi
		if [ ! -z "$USEDOCKER" ] ; then
			echo "Fire up the Memcached docker"
			DOCKER_SERVICE_CONTAINER_ID=$(docker run -d \
				-p 11212:11212 \
				memcached memcached -p 11212)
			echo "Waiting for Memcached initialisation ..."
			if ! apps/files_external/tests/env/wait-for-connection localhost 11212 30; then
				echo "[ERROR] Waited 30 seconds, no Memcached response" >&2
				exit 1
			fi
			echo "Memcached is up."
		fi
		cp tests/memcached.config.php config/memcached.config.php
	fi

	_DB=$DB

	# drop database
	if [ "$DB" == "mysql" ] ; then
		if [ ! -z "$USEDOCKER" ] ; then
			echo "Fire up the mysql docker"
			DOCKER_CONTAINER_ID=$(docker run \
				-v $BASEDIR/tests/docker/mariadb:/etc/mysql/conf.d \
				-e MYSQL_ROOT_PASSWORD=owncloud \
				-e MYSQL_USER="$DATABASEUSER" \
				-e MYSQL_PASSWORD=owncloud \
				-e MYSQL_DATABASE="$DATABASENAME" \
				-d mysql)
			DATABASEHOST=$(docker_get_ip "$DOCKER_CONTAINER_ID")

		else
			if [ "mysql" != "$(mysql --version | grep -o mysql)" ] ; then
				echo "Your mysql binary is not provided by mysql"
				echo "To use the docker container set the USEDOCKER environment variable"
				exit -1
			fi
			mysql -u "$DATABASEUSER" -powncloud -e "DROP DATABASE IF EXISTS $DATABASENAME" -h $DATABASEHOST || true
		fi
		echo "Waiting for MySQL initialisation ..."
		if ! apps/files_external/tests/env/wait-for-connection $DATABASEHOST 3306 300; then
			echo "[ERROR] Waited 300 seconds, no response" >&2
			exit 1
		fi
	fi
	if [ "$DB" == "mysqlmb4" ] ; then
		if [ ! -z "$USEDOCKER" ] ; then
			echo "Fire up the mysql docker"
			DOCKER_CONTAINER_ID=$(docker run \
				-v $BASEDIR/tests/docker/mysqlmb4:/etc/mysql/conf.d \
				-e MYSQL_ROOT_PASSWORD=owncloud \
				-e MYSQL_USER="$DATABASEUSER" \
				-e MYSQL_PASSWORD=owncloud \
				-e MYSQL_DATABASE="$DATABASENAME" \
				-d mysql:5.7 \
				--innodb_large_prefix=true \
				--innodb_file_format=barracuda \
				--innodb_file_per_table=true)

			DATABASEHOST=$(docker_get_ip "$DOCKER_CONTAINER_ID")

		else
			if [ "mysql" != "$(mysql --version | grep -o mysql)" ] ; then
				echo "Your mysql binary is not provided by mysql"
				echo "To use the docker container set the USEDOCKER environment variable"
				exit -1
			fi
			mysql -u "$DATABASEUSER" -powncloud -e "DROP DATABASE IF EXISTS $DATABASENAME" -h $DATABASEHOST || true
		fi

		echo "Waiting for MySQL(utf8mb4) initialisation ..."

		if ! apps/files_external/tests/env/wait-for-connection $DATABASEHOST 3306 300; then
			echo "[ERROR] Waited 300 seconds, no response" >&2
			exit 1
		fi
		sleep 1

		echo "MySQL(utf8mb4)  is up."
		_DB="mysql"

		cp tests/docker/mysqlmb4.config.php config
	fi
	if [ "$DB" == "mariadb" ] ; then
		if [ ! -z "$USEDOCKER" ] ; then
			echo "Fire up the mariadb docker"
			DOCKER_CONTAINER_ID=$(docker run \
				-v $BASEDIR/tests/docker/mariadb:/etc/mysql/conf.d \
				-e MYSQL_ROOT_PASSWORD=owncloud \
				-e MYSQL_USER="$DATABASEUSER" \
				-e MYSQL_PASSWORD=owncloud \
				-e MYSQL_DATABASE="$DATABASENAME" \
				-d mariadb)
			DATABASEHOST=$(docker_get_ip "$DOCKER_CONTAINER_ID")

			echo "Waiting for MariaDB initialisation ..."
			if ! apps/files_external/tests/env/wait-for-connection $DATABASEHOST 3306 300; then
				echo "[ERROR] Waited 300 seconds, no response" >&2
				exit 1
			fi

			echo "MariaDB is up."

		else
			if [ "MariaDB" != "$(mysql --version | grep -o MariaDB)" ] ; then
				echo "Your mysql binary is not provided by MariaDB"
				echo "To use the docker container set the USEDOCKER environment variable"
				exit -1
			fi
			mysql -u "$DATABASEUSER" -powncloud -e "DROP DATABASE IF EXISTS $DATABASENAME" -h $DATABASEHOST || true
		fi

		echo "Waiting for MariaDB initialisation ..."
		if ! apps/files_external/tests/env/wait-for-connection $DATABASEHOST 3306 300; then
			echo "[ERROR] Waited 300 seconds, no response" >&2
			exit 1
		fi

		#Reset _DB to mysql since that is what we use internally
		_DB="mysql"
	fi
	if [ "$DB" == "pgsql" ] ; then
		if [ ! -z "$USEDOCKER" ] ; then
			echo "Fire up the postgres docker"
			DOCKER_CONTAINER_ID=$(docker run -e POSTGRES_DB="$DATABASENAME" -e POSTGRES_USER="$DATABASEUSER" -e POSTGRES_PASSWORD=owncloud -d postgres)
			DATABASEHOST=$(docker_get_ip "$DOCKER_CONTAINER_ID")

			echo "Waiting for Postgres initialisation ..."

			if ! apps/files_external/tests/env/wait-for-connection $DATABASEHOST 5432 60; then
				echo "[ERROR] Waited 60 seconds for $DATABASEHOST, no response" >&2
				exit 1
			fi

			echo "Postgres is up."
		else
			echo "Waiting for Postgres to be available ..."
			if ! apps/files_external/tests/env/wait-for-connection $DATABASEHOST 5432 60; then
				echo "[ERROR] Waited 60 seconds for $DATABASEHOST, no response" >&2
				exit 1
			fi
			echo "Give it 10 additional seconds ..."
			sleep 10

			dropdb -U "$DATABASEUSER" "$DATABASENAME" || true
		fi
	fi
	if [ "$DB" == "oci" ] ; then
		echo "Fire up the oracle docker"
		DOCKER_CONTAINER_ID=$(docker run -d deepdiver/docker-oracle-xe-11g)
		DATABASEHOST=$(docker_get_ip "$DOCKER_CONTAINER_ID")

		echo "Waiting for Oracle initialization ... "

		# Try to connect to the OCI host via sqlplus to ensure that the connection is already running
		for i in {1..48}
		do
			if sqlplus "autotest/owncloud@(DESCRIPTION=(ADDRESS=(PROTOCOL=TCP)(Host=$DATABASEHOST)(Port=1521))(CONNECT_DATA=(SID=XE)))" < /dev/null | grep 'Connected to'; then
				break;
			fi
			sleep 5
		done

		DATABASEUSER=autotest
		DATABASENAME='XE'
	fi

	# trigger installation
	echo "Installing ...."
	"$PHP" ./occ maintenance:install -vvv --database="$_DB" --database-name="$DATABASENAME" --database-host="$DATABASEHOST" --database-user="$DATABASEUSER" --database-pass=owncloud --admin-user="$ADMINLOGIN" --admin-pass=admin --data-dir="$DATADIR"

	#test execution
	echo "Testing with $DB ..."
	cd tests
	rm -rf "coverage-html-$DB"
	mkdir "coverage-html-$DB"
	"$PHP" -f enable_all.php | grep -i -C9999 error && echo "Error during setup" && exit 101
	if [[ "$_XDEBUG_CONFIG" ]]; then
		export XDEBUG_CONFIG=$_XDEBUG_CONFIG
	fi
	GROUP=''
	if [ "$TEST_SELECTION" == "QUICKDB" ]; then
		GROUP='--group DB --exclude-group SLOWDB'
	fi
	if [ "$TEST_SELECTION" == "DB" ]; then
		GROUP='--group DB --group SLOWDB'
	fi
	if [ "$TEST_SELECTION" == "NODB" ]; then
		GROUP='--exclude-group DB --exclude-group SLOWDB'
	fi
	if [ "$TEST_SELECTION" == "PRIMARY-s3" ]; then
		GROUP='--group PRIMARY-s3'
	fi
	if [ "$TEST_SELECTION" == "PRIMARY-azure" ]; then
		GROUP='--group PRIMARY-azure'
	fi
	if [ "$TEST_SELECTION" == "PRIMARY-swift" ]; then
		GROUP='--group PRIMARY-swift'
	fi

	COVER=''
	if [ -z "$COVERAGE" ]; then
		echo "No coverage. Set COVERAGE=1 to enable it."
	else
		COVER="--coverage-clover autotest-clover-$DB.xml --coverage-html coverage-html-$DB"
	fi

	echo "$PHPUNIT" --fail-on-warning --fail-on-risky --display-warnings --display-deprecations --display-phpunit-deprecations --colors=always --configuration phpunit-autotest.xml $GROUP $COVER --log-junit "autotest-results-$DB.xml" "$2" "$3"
	DB_ROOT_PASSWORD=owncloud DB_ROOT_USER="root" "$PHPUNIT" --fail-on-warning --fail-on-risky --display-warnings --display-deprecations --display-phpunit-deprecations --colors=always --configuration phpunit-autotest.xml $GROUP $COVER --log-junit "autotest-results-$DB.xml" "$2" "$3"
	RESULT=$?

	if [ "$PRIMARY_STORAGE_CONFIG" == "swift" ] ; then
		cd ..
		echo "Kill the swift docker"
		tests/objectstore/stop-swift-ceph.sh
	fi

	if [ ! -z "$DOCKER_CONTAINER_ID" ] ; then
		echo "Kill the docker $DOCKER_CONTAINER_ID"
		docker stop $DOCKER_CONTAINER_ID
		docker rm -f $DOCKER_CONTAINER_ID
		unset DOCKER_CONTAINER_ID
	fi

	if [ ! -z "$DOCKER_SERVICE_CONTAINER_ID" ] ; then
		echo "Kill the service docker $DOCKER_SERVICE_CONTAINER_ID"
		docker stop $DOCKER_SERVICE_CONTAINER_ID
		docker rm -f $DOCKER_SERVICE_CONTAINER_ID
		unset DOCKER_SERVICE_CONTAINER_ID
	fi
}

#
# start test execution
#
if [ ! -z "$EXTERNAL_STORAGE" ] ; then
	execute_external_tests "$EXTERNAL_STORAGE"
elif [ -z "$1" ]
  then
	# run all known database configs
	for DBCONFIG in $DBCONFIGS; do
		execute_tests "$DBCONFIG"
	done
else
	FILENAME="$2"
	if [ ! -z "$2" ] && [ ! -f "tests/$FILENAME" ] && [ "${FILENAME:0:2}" != "--" ]; then
		FILENAME="../$FILENAME"
	fi
	execute_tests "$1" "$FILENAME" "$3"
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
#  - to enable dropdb I decided to add following line to pg_hba.conf
#    (this is not the safest way but I don't care for the testing machine):
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
