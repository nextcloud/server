#!/usr/bin/env bash

set -e

# Run phpunit tests
cd tests
phpunit --configuration phpunit.xml

# Create coverage report
wget https://scrutinizer-ci.com/ocular.phar
php ocular.phar code-coverage:upload --access-token="09d3585a3ed80b16c3b9eed9912b27cfd85e6ed8c190d95893a02bef73dbde68" --format=php-clover clover.xml
