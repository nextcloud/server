#!/usr/bin/env bash

REPODIR=`git rev-parse --show-toplevel`
cd $REPODIR

if [ -e "php-cs-fixer" ]
then
    echo "php-cs-fixer found"
else
    echo "php-cs-fixer not found: downloading"
    php -r "copy('http://cs.sensiolabs.org/download/php-cs-fixer-v2.phar', 'php-cs-fixer');"
fi

php ./php-cs-fixer $1 fix

status=$?
if [[ $1 == '--dry-run' ]]
then
    if [[ $status != 0 ]]
    then
        echo "Code style problems found, please run 'bash build/php-cs-fixer.sh' and commit the result."
    fi
fi
exit $status
