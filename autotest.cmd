::
:: ownCloud
::
:: @author Thomas Müller
:: @author Tobias Ramforth (translated into Windows batch file)
::
:: @copyright 2012 Thomas Müller thomas.mueller@tmit.eu
::
@echo off

set DATADIR=data-autotest
set BASEDIR=%~dp0

:: create autoconfig for sqlite, mysql and postgresql
echo ^<?php                                      > .\tests\autoconfig-sqlite.php
echo $AUTOCONFIG ^= array ^(                     >> .\tests\autoconfig-sqlite.php
echo  'installed' ^=^> false^,                   >> .\tests\autoconfig-sqlite.php
echo  'dbtype' ^=^> 'sqlite'^,                   >> .\tests\autoconfig-sqlite.php
echo  'dbtableprefix' ^=^> 'oc_'^,               >> .\tests\autoconfig-sqlite.php
echo  'adminlogin' ^=^> 'admin'^,                >> .\tests\autoconfig-sqlite.php
echo  'adminpass' ^=^> 'admin'^,                 >> .\tests\autoconfig-sqlite.php
echo  'directory' ^=^> '%BASEDIR%%DATADIR%'^,    >> .\tests\autoconfig-sqlite.php
echo ^)^;                                        >> .\tests\autoconfig-sqlite.php

echo ^<?php                                      > .\tests\autoconfig-mysql.php
echo $AUTOCONFIG ^= array ^(                     >> .\tests\autoconfig-mysql.php
echo   'installed' ^=^> false^,                  >> .\tests\autoconfig-mysql.php
echo   'dbtype' ^=^> 'mysql'^,                   >> .\tests\autoconfig-mysql.php
echo   'dbtableprefix' ^=^> 'oc_'^,              >> .\tests\autoconfig-mysql.php
echo   'adminlogin' ^=^> 'admin'^,               >> .\tests\autoconfig-mysql.php
echo   'adminpass' ^=^> 'admin'^,                >> .\tests\autoconfig-mysql.php
echo   'directory' ^=^> '%BASEDIR%%DATADIR%'^,   >> .\tests\autoconfig-mysql.php
echo   'dbuser' ^=^> 'oc_autotest'^,             >> .\tests\autoconfig-mysql.php
echo   'dbname' ^=^> 'oc_autotest'^,             >> .\tests\autoconfig-mysql.php
echo   'dbhost' ^=^> 'localhost'^,               >> .\tests\autoconfig-mysql.php
echo   'dbpass' ^=^> 'owncloud'^,                >> .\tests\autoconfig-mysql.php
echo ^)^;                                        >> .\tests\autoconfig-mysql.php

echo ^<?php                                      > .\tests\autoconfig-pgsql.php
echo $AUTOCONFIG ^= array ^(                     >> .\tests\autoconfig-pgsql.php
echo   'installed' ^=^> false^,                  >> .\tests\autoconfig-pgsql.php
echo   'dbtype' ^=^> 'pgsql'^,                   >> .\tests\autoconfig-pgsql.php
echo   'dbtableprefix' ^=^> 'oc_'^,              >> .\tests\autoconfig-pgsql.php
echo   'adminlogin' ^=^> 'admin'^,               >> .\tests\autoconfig-pgsql.php
echo   'adminpass' ^=^> 'admin'^,                >> .\tests\autoconfig-pgsql.php
echo   'directory' ^=^> '%BASEDIR%%DATADIR%'^,   >> .\tests\autoconfig-pgsql.php
echo   'dbuser' ^=^> 'oc_autotest'^,             >> .\tests\autoconfig-pgsql.php
echo   'dbname' ^=^> 'oc_autotest'^,             >> .\tests\autoconfig-pgsql.php
echo   'dbhost' ^=^> 'localhost'^,               >> .\tests\autoconfig-pgsql.php
echo   'dbpass' ^=^> 'owncloud'^,                >> .\tests\autoconfig-pgsql.php
echo ^)^;                                        >> .\tests\autoconfig-pgsql.php

echo localhost:5432:*:oc_autotest:owncloud > %APPDATA%\postgresql\pgpass.conf

::
:: start test execution
::
::call:execute_tests "sqlite"
call:execute_tests "mysql"
::call:execute_tests "mssql"
::call:execute_tests "ora"
::call:execute_tests "pgsql"

goto:eof

:execute_tests
	echo "Setup environment for %~1 testing ..."
	:: back to root folder
	cd %BASEDIR%

	:: revert changes to tests\data
	git checkout tests\data\*

	:: reset data directory
	rmdir /s /q %DATADIR%
	md %DATADIR%

	:: remove the old config file
	:: del /q /f config\config.php
	copy /y tests\preseed-config.php config\config.php

	:: drop database
	if "%~1" == "mysql" mysql -u oc_autotest -powncloud -e "DROP DATABASE oc_autotest"
	
	if "%~1" == "pgsql" dropdb -h localhost -p 5432 -U oc_autotest -w oc_autotest
	
	:: copy autoconfig
	copy /y %BASEDIR%\tests\autoconfig-%~1.php %BASEDIR%\config\autoconfig.php

	:: trigger installation
	php -f index.php

	::test execution
	echo "Testing with %~1 ..."
	cd tests
	rmdir /s /q coverage-html-%~1
	md coverage-html-%~1
	php -f enable_all.php
	::phpunit --log-junit autotest-results-%~1.xml --coverage-clover autotest-clover-%~1.xml --coverage-html coverage-html-%~1
	::phpunit --bootstrap bootstrap.php --configuration phpunit.xml
	php win32-phpunit.php --bootstrap bootstrap.php --configuration phpunit.xml --log-junit autotest-results-%~1.xml --coverage-clover autotest-clover-%~1.xml --coverage-html coverage-html-%~1
	echo "Done with testing %~1 ..."
	cd %BASEDIR%
goto:eof

::
:: NOTES on mysql:
::  - CREATE USER 'oc_autotest'@'localhost' IDENTIFIED BY 'owncloud';
::  - grant access permissions: grant all on oc_autotest.* to 'oc_autotest'@'localhost';
::
:: NOTES on pgsql:
::  - su - postgres
::  - createuser -P (enter username and password and enable superuser)
::  - to enable dropdb I decided to add following line to pg_hba.conf (this is not the safest way but I don't care for the testing machine):
:: local	all	all	trust
::

