#!/usr/bin/env bash

COMPOSER_COMMAND="php composer.phar"

if [ -e "composer.phar" ]
then
  echo "Composer found: checking for update"
  $COMPOSER_COMMAND self-update
else
  echo "Composer not found: fetching"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --2
  php -r "unlink('composer-setup.php');"
fi


COMPOSER_VERSION=$($COMPOSER_COMMAND --version | cut -d" " -f3)
COMPOSER_MAJOR_VERSION=$(echo "$COMPOSER_VERSION" | cut -d"." -f1)
COMPOSER_MINOR_VERSION=$(echo "$COMPOSER_VERSION" | cut -d"." -f2)
COMPOSER_PATCH_VERSION=$(echo "$COMPOSER_VERSION" | cut -d"." -f3)

if ! [ "$COMPOSER_MAJOR_VERSION" -gt 2 -o \( "$COMPOSER_MAJOR_VERSION" -eq 2 -a "$COMPOSER_MINOR_VERSION" -ge 6 \) -o \( "$COMPOSER_MAJOR_VERSION" -eq 2 -a "$COMPOSER_MINOR_VERSION" -eq 5 -a "$COMPOSER_PATCH_VERSION" -ge 5 \) ]; then
	echo "composer version >= 2.5.5 required. Version found: $COMPOSER_VERSION" >&2
	exit 1
fi

REPODIR=`git rev-parse --show-toplevel`

#Redump the main autoloader
echo
echo "Regenerating main autoloader"
$COMPOSER_COMMAND dump-autoload -d $REPODIR

for app in ${REPODIR}/apps/*; do
	if git check-ignore ${app} -q ; then
		echo
		echo "${app} is not shipped. Ignoring autoloader regeneration"
		continue
	fi
    if [[ -d $app ]]; then
		echo
		echo "Regenerating composer files for ${app}"
		$COMPOSER_COMMAND i --no-dev -d ${app}/composer || exit 1
		$COMPOSER_COMMAND dump-autoload -d ${app}/composer || exit 1
    fi
done

files=`git diff --name-only`
composerfile=false
for file in $files
do
    if [[ $file == *autoload_classmap* ]]
    then
        composerfile=true
        break
    fi
done

rm composer.phar

echo
if [ $composerfile = true ]
then
    echo "The autoloaders are not up to date"
    echo "Please run: bash build/autoloaderchecker.sh"
    echo "And commit the result"
    exit 1
else
    echo "Autoloader up to date. Carry on"
    exit 0
fi
