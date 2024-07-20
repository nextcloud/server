#!/bin/bash

# SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

wget https://phpdoc.org/phpDocumentor.phar

mkdir -p api/

php phpDocumentor.phar --target=./api --directory=./lib/public --title="Nextcloud PHP API ($BRANCH)"
