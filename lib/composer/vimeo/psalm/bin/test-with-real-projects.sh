#!/usr/bin/env bash

set -e
set -x

cd /tmp/
mkdir -p testing-with-real-projects
cd testing-with-real-projects

case $1 in
phpunit)
	git clone git@github.com:muglug/phpunit.git
	cd phpunit
	composer install
	~/project/build/psalm.phar --config=.psalm/config.xml --monochrome --show-info=false
	~/project/build/psalm.phar --config=.psalm/static-analysis.xml --monochrome
	;;

collections)
	git clone git@github.com:muglug/collections.git
	cd collections
	composer install
	~/project/psalm --monochrome --show-info=false
	;;

proxymanager)
	git clone git@github.com:muglug/ProxyManager.git
	cd ProxyManager
	composer install
	~/project/psalm --monochrome
	;;

psl)
	git clone git@github.com:azjezz/psl.git
	cd psl
	composer install --ignore-platform-reqs
	#~/project/psalm --monochrome
	;;

laravel)
	git clone git@github.com:muglug/framework.git
	cd framework
	composer install
	~/project/psalm --monochrome
	;;
*)
	echo "Usage: test-with-real-projects.sh {phpunit|collections|proxymanager|laravel|psl}"
	exit 1
esac
