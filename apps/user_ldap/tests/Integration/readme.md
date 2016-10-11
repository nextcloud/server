# Requirements #

Have (as in do copy if not already done) the following files from https://github.com/owncloud/administration/tree/master/ldap-testing copied into the directory "setup-scripts":

 * start.sh
 * stop.sh
 * config.php

Configure config.php according to your needs, also have a look into the LDAP and network settings in start.sh and stop.sh.

# Usage #

The basic command to run a test is:

```# ./run-test.sh [phpscript]```

Yes, run it as root from within this directory.

Example:

```
$ sudo ./run-test.sh lib/IntegrationTestAccessGroupsMatchFilter.php 
71cbe88a4993e67066714d71c1cecc5ef26a54911a208103cb6294f90459e574
c74dc0155db4efa7a0515d419528a8727bbc7596601cf25b0df05e348bd74895
CONTAINER ID        IMAGE                       COMMAND             CREATED             STATUS                  PORTS                           NAMES
c74dc0155db4        osixia/phpldapadmin:0.5.1   "/sbin/my_init"     1 seconds ago       Up Less than a second   80/tcp, 0.0.0.0:8443->443/tcp   docker-phpldapadmin   
71cbe88a4993        nickstenning/slapd:latest   "/sbin/my_init"     1 seconds ago       Up Less than a second   127.0.0.1:7770->389/tcp         docker-slapd          

LDAP server now available under 127.0.0.1:7770 (internal IP is 172.17.0.78)
phpldapadmin now available under https://127.0.0.1:8443

created user : Alice Ealic
created group : RedGroup
created group : BlueGroup
created group : GreenGroup
created group : PurpleGroup
running case1 
running case2 
Tests succeeded
Stopping and resetting containers
docker-slapd
docker-phpldapadmin
docker-slapd
docker-phpldapadmin
```

# How it works #

1. start.sh is executed which brings up a fresh and clean OpenLDAP in Docker.
2. The provided test script is executed. It also outputs results.
3. stop.sh is executed to shut down OpenLDAP

# Beware #

This is quick solution for basically one test case. With expension this mechanism should be improved as well.

It does not run automatically, unless you do it. No integration with any testing framework.

exceptionOnLostConnection.php is not part of this mechanism. Read its source and run it isolated. While you're at it, port it :þ

