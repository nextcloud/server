#!/bin/sh

if [ $1 ] ; then
  TESTSCRIPT=$1
else
  echo "No test file given"                                                                                                                                                    exit
fi

if [ ! -e "$TESTSCRIPT" ] ; then
    echo "Test file does not exist"
    exit
fi

# sleep is necessary, otherwise the LDAP server cannot be connected to, yet.
WEBUSER=`stat -c %U ../../../../config/config.php`
setup-scripts/start.sh > /dev/null && sleep 5 && \
    sudo -u "$WEBUSER" ../../../../occ app:enable user_ldap && \
    sudo -u "$WEBUSER" php -f "$TESTSCRIPT"
CODE=$?
setup-scripts/stop.sh > /dev/null
exit ${CODE}
