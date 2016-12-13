#!/usr/bin/env bash

#Make sure we are on the latest composer
if [ -e "composer.phar" ]
then
    echo "Composer found: checking for update"
    php composer.phar self-update
else
    echo "Composer not found: fetching"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
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
