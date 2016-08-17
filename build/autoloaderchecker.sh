#!/usr/bin/env bash

#Make sure we are on the latest composer
if [ -e "composer.phar" ]
then
    echo "Composer found: checking for update"
    php composer.phar self-update
else
    echo "Composer not found: fetching"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php -r "if (hash_file('SHA384', 'composer-setup.php') === 'e115a8dc7871f15d853148a7fbac7da27d6c0030b848d9b3dc09e2a0388afed865e6a3d6b3c0fad45c48e2b5fc1196ae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
fi

REPODIR=`git rev-parse --show-toplevel`

#Redump the autoloader
echo
echo "Regenerating autoloader"
php composer.phar dump-autoload -d $REPODIR

files=`git diff --name-only`
composerfile=false
for file in $files
do
    if [[ $file == lib/composer/* ]]
    then
        composerfile=true
        break
    fi
done

echo
if [ $composerfile = true ]
then
    echo "The autoloader is not up to date"
    echo "Please run: bash build/autoloaderchecker.sh"
    echo "And commit the result"
    exit 1
else
    echo "Autoloader up to date. Carry on"
    exit 0
fi
