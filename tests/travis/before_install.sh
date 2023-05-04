#!/bin/bash
#
# ownCloud
#
# @author Thomas Müller
# @copyright 2014 Thomas Müller thomas.mueller@tmit.eu
#

set -e

WORKDIR=$PWD
DB=$1
echo "Work directory: $WORKDIR"
echo "Database: $DB"

if [ "$DB" == "mysql" ] ; then
  echo "Setting up mysql ..."
  mysql -e 'create database oc_autotest;'
  mysql -u root -e "CREATE USER 'oc_autotest'@'localhost' IDENTIFIED BY 'owncloud'";
  mysql -u root -e "grant all on oc_autotest.* to 'oc_autotest'@'localhost'";
  mysql -e "SELECT User FROM mysql.user;"
fi

if [ "$DB" == "pgsql" ] ; then
  createuser -U travis -s oc_autotest
fi

#if [ "$DB" == "oracle" ] ; then
#  if [ ! -f before_install_oracle.sh ]; then
#    wget https://raw.githubusercontent.com/owncloud/administration/master/travis-ci/before_install_oracle.sh
#  fi
#  bash ./before_install_oracle.sh
#fi

#
# copy custom php.ini settings
#
if [ $(phpenv version-name) != 'hhvm' ]; then
  phpenv config-add tests/travis/custom.ini
fi
