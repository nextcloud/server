# How to run the files external unit tests

## Components

The files_external relies - as the name already says - on external file system
providers. To test easily against such a provider we use some scripts to setup
a provider (and of course also cleanup that provider). Those scripts can be
found in the `tests/env` folder of the files_external app.

### Naming Conventions

The current implementation supports a script that starts with `start-` for the
setup step which is executed before the PHPUnit run and an optional script
starting with `stop-` (and have the same ending as the start script) to cleanup
the provider. For example: `start-webdav-ownCloud.sh` and
`stop-webdav-ownCloud.sh`. As a second requirement after this prefix there has
to be the name of the backend test suite. In the above example the test suite
`tests/backends/webdav.php` is used. The last part is a name that can be chosen
freely.

## Hands-on way of unit test execution

Run all files_external unit tests by invoking the following in the ownCloud
core root folder:

    ./autotest-external.sh

This script supports to get passed a database as first argument:

    ./autotest-external.sh sqlite

You can also pass the name of the external file system provider as a second
argument that should be executed. This is the name of the script without the
prefix `start-` (or `stop-`) and without the extension `.sh` from the above
mentioned components in `test/env`. So if you want to start the WebDAV backend
tests against an ownCloud instance you can run following:

    ./autotest-external.sh sqlite webdav-ownCloud

This runs the script `start-webdav-ownCloud.sh` from the `tests/env` folder,
then runs the unit test suite from `backends/webdav.php` (because the middle part of
the name of the script is `webdav`) and finally tries to call
`stop-webdav-ownCloud.sh` for cleanup purposes.

If `common-tests` is supplied as second argument it will skip the backend specific
part completely and just run the common files_external unit tests:

    ./autotest-external.sh sqlite common-tests

## The more manual way of unit test execution

If you want to debug your external storage provider, you maybe don't want to
fire it up, execute the unit tests and clean everything up for each debugging
step. In this case you can simply start the external storage provider instance
and run the unit test multiple times against the instance for debugging purposes.
To do this you just need to follow these steps (from within
`apps/files_external/tests`):

  1. run the start step (`env/start-BACKEND-NAME.sh`) or start the environment by
     hand (i.e. setting up an instance manually in a virtual box)
  2. run the unit tests with following command (you can repeat that step multiple times):
     `phpunit --configuration ../../../tests/phpunit-autotest-external.xml backends/BACKEND.php`
  3. call the cleanup script (`env/stop-BACKEND-NAME.sh`) or cleanup by hand
