#!/usr/bin/env bash

set -e

composer bin box install

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null 2>&1 && pwd )"

php $DIR/improve_class_alias.php

vendor/bin/box compile

if [[ "$GPG_ENCRYPTION" != '' ]] ; then
    echo $GPG_ENCRYPTION | gpg --passphrase-fd 0 keys.asc.gpg
    gpg --batch --yes --import keys.asc
    echo $SIGNING_KEY | gpg --passphrase-fd 0 -u 8A03EA3B385DBAA1 --armor --detach-sig build/psalm.phar
fi
