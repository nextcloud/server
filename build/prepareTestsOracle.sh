#!/bin/bash
#
# ownCloud - prepareTestOracle.sh
#
# @author Morris Jobke
# @copyright 2014 Morris Jobke hey@morrisjobke.de
#

DATABASENAME=$1
DATABASEUSER=$2
ADMINLOGIN=$3
DATADIR=$4

# set oracle home if it is not set
TRAVIS_ORACLE_HOME="/usr/lib/oracle/xe/app/oracle/product/10.2.0/server"
[ -z "$ORACLE_HOME" ] && ORACLE_HOME=$TRAVIS_ORACLE_HOME

echo "Load Oracle environment variables so that we can run 'sqlplus'."
 . $ORACLE_HOME/bin/oracle_env.sh

echo "drop the database"
sqlplus64 -s -l / as sysdba <<EOF
	drop user $DATABASENAME cascade;
EOF

echo "create the database"
sqlplus64 -s -l / as sysdba <<EOF
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

# there was a maximum cursor limit exceed
# therefore increase the limit
sqlplus64 -s -l / as sysdba <<EOF
	ALTER SYSTEM SET open_cursors = 1000 SCOPE=BOTH;
EOF

cat > ./config/autoconfig.php <<DELIM
<?php
\$AUTOCONFIG = array (
  'installed' => false,
  'dbtype' => 'oci',
  'dbtableprefix' => 'oc_',
  'adminlogin' => '$ADMINLOGIN',
  'adminpass' => 'admin',
  'directory' => '$DATADIR',
  'dbuser' => '$DATABASEUSER',
  'dbname' => 'XE',
  'dbhost' => 'localhost',
  'dbpass' => 'owncloud',
);
DELIM

