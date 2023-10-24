#!/bin/bash

wget https://phpdoc.org/phpDocumentor.phar

mkdir -p api/

export PHP_VERSION=8.1
php phpDocumentor.phar -t "./api" -d "./lib/public" --title="Nextcloud PHP API ($BRANCH)"
