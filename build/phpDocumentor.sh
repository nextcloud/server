#!/bin/bash

wget https://phpdoc.org/phpDocumentor.phar

mkdir -p api/

php phpDocumentor.phar --target=./api --directory=./lib/public --title="Nextcloud PHP API ($BRANCH)"
