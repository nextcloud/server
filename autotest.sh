#preparation
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
php -f index.php -- xml


#
# TODO: create config for mysql and postgres
#

