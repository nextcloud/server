#!/bin/bash

wget https://phpdoc.org/phpDocumentor.phar

mkdir -p api/

php7.4 phpDocumentor.phar -t "./api" -d "./lib/public" --title="Nextcloud PHP API ($BRANCH)"
