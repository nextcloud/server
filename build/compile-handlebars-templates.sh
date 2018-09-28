#!/usr/bin/env bash

REPODIR=`git rev-parse --show-toplevel`

cd $REPODIR

# Settings
handlebars -n OC.Settings.Templates  settings/js/authtoken.handlebars -f settings/js/templates.js

# Contactsmenu
handlebars -n OC.ContactsMenu.Templates core/js/contactsmenu -f core/js/contactsmenu_templates.js

# Files app
handlebars -n OCA.Files.FileSummary.Templates apps/files/js/filesummary.handlebars -f apps/files/js/filesummary_template.js

if [[ $(git diff --name-only) ]]; then
    echo "Please submit your compiled handlebars templates"
    exit 1
fi

exit 0
