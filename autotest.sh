#
# sqlite testing now
#
echo "Setup environment for sqlite testing ..."
DATADIR=data-autotest
rm -rf $DATADIR
mkdir $DATADIR
rm -rf config/config.php
#cp autotest/config.sqlite.php config/autoconfig.php
cat > ./config/autoconfig.php <<DELIM
<?php
\$AUTOCONFIG = array (
  'installed' => false,
  'dbtype' => 'sqlite',
  'dbtableprefix' => 'oc_',
  'datadirectory' => 'data',
  'adminlogin' => 'admin',
  'adminpass' => 'admin',
  'directory' => '$PWD/$DATADIR',
);
DELIM

php -f index.php

#test execution
echo 'Testing with sqlite ...'
cd tests
php -f index.php -- xml > autotest-results-sqlite.xml
cd ..
git checkout tests/data/*

#
# mysql testing now
#
# NOTES:
#  - CREATE USER 'oc_autotest'@'localhost' IDENTIFIED BY 'owncloud';
#  - grant access permissions: grant all on oc_autotest.* to 'oc_autotest'@'localhost';
#
echo "Setup environment for MySql testing ..."
DATADIR=data-autotest
rm -rf $DATADIR
mkdir $DATADIR
rm -rf config/config.php
cat > ./config/autoconfig.php <<DELIM
<?php
\$AUTOCONFIG = array (
  'installed' => false,
  'dbtype' => 'mysql',
  'dbtableprefix' => 'oc_',
  'datadirectory' => 'data',
  'adminlogin' => 'admin',
  'adminpass' => 'admin',
  'directory' => '$PWD/$DATADIR',
  'dbuser' => 'oc_autotest',	
  'dbname' => 'oc_autotest',	
  'dbhost' => 'localhost',
  'dbpass' => 'owncloud',	
);
DELIM

#drop the database
mysql -u oc_autotest -powncloud -e "DROP DATABASE oc_autotest"

#setup
php -f index.php

#test execution
echo 'Testing with MySql ...'
cd tests
php -f index.php -- xml > autotest-results-MySql.xml
cd ..
git checkout tests/data/*

#
# TODO: create config for postgres
#

