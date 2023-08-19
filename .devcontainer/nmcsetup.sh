#!/bin/bash
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/../" >/dev/null 2>&1 && pwd )"

cd $DIR/

# after installation, do some MagentaCLOUD specific setups to start closer to production

# customapps directory split
cp .devcontainer/apps.config.php config/apps.config.php
cp .devcontainer/nmc.config.php config/nmc.config.php

# disable user themeing
sudo -u ${APACHE_RUN_USER} php occ theming:config disable-user-theming yes

# fixed themeing for MagentaCLOUD
cp .devcontainer/theme.config.php config/theme.config.php

# refresh .htaccess for short URL notation
sudo -u ${APACHE_RUN_USER} php occ maintenance:update:htaccess

# "Organisational" setting
sudo -u ${APACHE_RUN_USER} php occ theming:config color "#e20074"   # don't use uppercase letters!
sudo -u ${APACHE_RUN_USER} php occ theming:config name MagentaCLOUD
sudo -u ${APACHE_RUN_USER} php occ theming:config slogan "Alle Dateien sicher an einem Ort"
sudo -u ${APACHE_RUN_USER} php occ theming:config imprintUrl "http://www.telekom.de/impressum"
sudo -u ${APACHE_RUN_USER} php occ theming:config privacyUrl "https://static.magentacloud.de/Datenschutz"

# app settings
sudo -u ${APACHE_RUN_USER} php occ config:app:set theming AndroidClientUrl --value \
    "https://play.google.com/store/apps/details?=com.t_systems.android.webdav"
sudo -u ${APACHE_RUN_USER} php occ config:app:set theming iTunesAppId --value "312838242"
sudo -u ${APACHE_RUN_USER} php occ config:app:set theming iOSClientUrl --value \
    "https://apps.apple.com/us/app/magentacloud-cloud-speicher/id312838242"

# enable/disable apps
sudo -u ${APACHE_RUN_USER} php occ app:enable nmctheme
sudo -u ${APACHE_RUN_USER} php occ app:disable dashboard  # may remove as soon as dashboard CR is implemented