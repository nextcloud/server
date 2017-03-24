#!/usr/bin/env bash

#Regenerate the vendors core.js
echo
echo "Regenerating core/vendor/core.js"

d=`dirname $(readlink -f $0)`

php $d/mergejs.php

files=`git diff --name-only`

for file in $files
do
    if [[ $file == core/vendor/core.js ]]
    then
        echo "The merged vendor file is not up to date"
        echo "Please run: php build/mergejs.php"
        echo "And commit the result"
        break
    fi
done

echo "Vendor js merged as expected. Carry on"
exit 0
