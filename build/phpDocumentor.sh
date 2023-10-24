#!/bin/bash

wget https://phpdoc.org/phpDocumentor.phar

mkdir -p api/

php phpDocumentor.phar run -t "./api" -d "./lib/public" --title="Nextcloud PHP API ($BRANCH)"
