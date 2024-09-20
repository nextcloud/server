#!/usr/bin/env bash
#
# SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later
#
REPODIR=`git rev-parse --show-toplevel`

cd $REPODIR

# Settings
node node_modules/handlebars/bin/handlebars -n OC.Settings.Templates  apps/settings/js/templates -f apps/settings/js/templates.js

# Files external
node node_modules/handlebars/bin/handlebars -n OCA.Files_External.Templates apps/files_external/js/templates -f apps/files_external/js/templates.js

if [[ $(git diff --name-only) ]]; then
    echo "Please submit your compiled handlebars templates"
    echo
    git diff
    exit 1
fi

echo "All up to date! Carry on :D"
exit 0
